import Badge, { jobStatusTone } from '@/Components/Badge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDate, jobLocation, jobStatusLabels } from '@/lib/format';
import {
    DndContext,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
    useDraggable,
    useDroppable,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

// Ao soltar um card, o navegador dispara um clique no link; ignoramos cliques logo após um arraste.
let lastDragEndedAt = 0;

function CardContent({ card }) {
    return (
        <>
            <p className="truncate text-sm font-medium text-gray-900">{card.candidate.name}</p>
            <p className="truncate text-xs text-gray-500">{card.candidate.email}</p>
            <div className="mt-2 flex items-center justify-between text-xs text-gray-400">
                <span>Inscrição em {formatDate(card.created_at)}</span>
                {card.status === 'rejected' && <Badge tone="red">Reprovada</Badge>}
            </div>
        </>
    );
}

function Card({ card, draggable }) {
    const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
        id: card.id,
        data: { card },
        disabled: !draggable,
    });

    return (
        <li
            ref={setNodeRef}
            className={
                'rounded-md bg-white p-3 shadow-sm ring-1 ring-gray-200 ' +
                (isDragging ? 'opacity-40 ' : '') +
                (draggable ? 'cursor-grab active:cursor-grabbing' : '') +
                (card.status === 'rejected' ? ' opacity-70' : '')
            }
            {...listeners}
            {...attributes}
        >
            <Link
                href={route('panel.applications.show', card.id)}
                className="block focus:outline-none"
                onClick={(event) => Date.now() - lastDragEndedAt < 300 && event.preventDefault()}
            >
                <CardContent card={card} />
            </Link>
        </li>
    );
}

function Column({ column, canManage, onLoadMore, loading }) {
    const { setNodeRef, isOver } = useDroppable({ id: `stage-${column.stage.id}`, data: { stageId: column.stage.id } });

    return (
        <section
            ref={setNodeRef}
            className={
                'flex w-72 shrink-0 flex-col rounded-lg p-3 transition-colors ' +
                (isOver ? 'bg-indigo-100 ring-2 ring-indigo-300' : 'bg-gray-200/60')
            }
        >
            <header className="mb-3 flex items-center justify-between px-1">
                <h3 className="text-sm font-semibold text-gray-700">{column.stage.name}</h3>
                <span className="rounded-full bg-white px-2 text-xs font-medium text-gray-600">{column.count}</span>
            </header>

            <ul className="flex min-h-24 flex-col gap-2">
                {column.data.map((card) => (
                    <Card key={card.id} card={card} draggable={canManage && card.status === 'active'} />
                ))}
            </ul>

            {column.next_cursor && (
                <button
                    type="button"
                    onClick={() => onLoadMore(column)}
                    disabled={loading}
                    className="mt-3 rounded-md py-1.5 text-xs font-medium text-indigo-700 hover:bg-white disabled:opacity-50"
                >
                    {loading ? 'Carregando…' : 'Carregar mais'}
                </button>
            )}
        </section>
    );
}

export default function Kanban({ job, columns: initialColumns, includeRejected, canManage }) {
    const [columns, setColumns] = useState(initialColumns);
    const [activeCard, setActiveCard] = useState(null);
    const [loadingStage, setLoadingStage] = useState(null);
    const [error, setError] = useState(null);

    const sensors = useSensors(
        // A distância mínima mantém o clique no card funcionando como link.
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(KeyboardSensor),
    );

    const moveCard = (current, card, toStageId) =>
        current.map((column) => {
            if (column.stage.id === card.job_stage_id) {
                return { ...column, count: column.count - 1, data: column.data.filter((item) => item.id !== card.id) };
            }

            if (column.stage.id === toStageId) {
                return { ...column, count: column.count + 1, data: [{ ...card, job_stage_id: toStageId }, ...column.data] };
            }

            return column;
        });

    const handleDragEnd = async ({ active, over }) => {
        setActiveCard(null);
        lastDragEndedAt = Date.now();

        const card = active.data.current?.card;
        const toStageId = over?.data.current?.stageId;

        if (!card || !toStageId || toStageId === card.job_stage_id) {
            return;
        }

        const previous = columns;
        setColumns((current) => moveCard(current, card, toStageId));
        setError(null);

        try {
            await axios.patch(route('panel.applications.move', card.id), { job_stage_id: toStageId });
        } catch (exception) {
            setColumns(previous);
            setError(
                exception.response?.data?.errors?.job_stage_id?.[0] ??
                    exception.response?.data?.message ??
                    'Não foi possível mover a candidatura. Tente novamente.',
            );
        }
    };

    const loadMore = async (column) => {
        setLoadingStage(column.stage.id);

        try {
            const { data } = await axios.get(route('panel.jobs.column', [job.id, column.stage.id]), {
                params: { cursor: column.next_cursor, reprovados: includeRejected ? 1 : 0 },
            });

            setColumns((current) =>
                current.map((item) =>
                    item.stage.id === column.stage.id
                        ? { ...item, data: [...item.data, ...data.data.filter((card) => !item.data.some((existing) => existing.id === card.id))], next_cursor: data.next_cursor }
                        : item,
                ),
            );
        } finally {
            setLoadingStage(null);
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 className="flex items-center gap-3 text-xl font-semibold leading-tight text-gray-800">
                            {job.title}
                            <Badge tone={jobStatusTone[job.status]}>{jobStatusLabels[job.status]}</Badge>
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">{jobLocation(job)}</p>
                    </div>
                    <div className="flex items-center gap-4 text-sm">
                        <label className="flex items-center gap-2 text-gray-600">
                            <input
                                type="checkbox"
                                checked={includeRejected}
                                className="rounded border-gray-300 text-indigo-600"
                                onChange={(e) =>
                                    router.get(route('panel.jobs.show', job.id), e.target.checked ? { reprovados: 1 } : {}, {
                                        preserveScroll: true,
                                    })
                                }
                            />
                            Mostrar reprovados
                        </label>
                        {canManage && (
                            <>
                                <Link href={route('panel.jobs.stages.index', job.id)} className="text-gray-600 hover:underline">
                                    Etapas
                                </Link>
                                <Link href={route('panel.jobs.edit', job.id)} className="text-gray-600 hover:underline">
                                    Editar
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Kanban: ${job.title}`} />

            <div className="px-4 py-6 sm:px-6 lg:px-8">
                {error && <p className="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">{error}</p>}
                {!canManage && (
                    <p className="mb-4 text-sm text-gray-500">Você tem acesso somente leitura a este processo seletivo.</p>
                )}

                <DndContext
                    sensors={sensors}
                    onDragStart={({ active }) => setActiveCard(active.data.current?.card ?? null)}
                    onDragCancel={() => setActiveCard(null)}
                    onDragEnd={handleDragEnd}
                >
                    <div className="flex gap-4 overflow-x-auto pb-4">
                        {columns.map((column) => (
                            <Column
                                key={column.stage.id}
                                column={column}
                                canManage={canManage}
                                onLoadMore={loadMore}
                                loading={loadingStage === column.stage.id}
                            />
                        ))}
                    </div>

                    <DragOverlay>
                        {activeCard && (
                            <div className="w-64 rotate-2 rounded-md bg-white p-3 shadow-lg ring-1 ring-indigo-300">
                                <CardContent card={activeCard} />
                            </div>
                        )}
                    </DragOverlay>
                </DndContext>
            </div>
        </AuthenticatedLayout>
    );
}
