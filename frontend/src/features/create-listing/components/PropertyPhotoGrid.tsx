'use client';

import { useCallback, useRef, useState, type Dispatch, type SetStateAction } from 'react';
import {
    DndContext,
    KeyboardSensor,
    PointerSensor,
    TouchSensor,
    closestCenter,
    type DragEndEvent,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    rectSortingStrategy,
    sortableKeyboardCoordinates,
    useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Loader2, RotateCw, Upload, X } from 'lucide-react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { uploadFile, FileTooLargeError } from '../api';
import {
    createUploadedPhoto,
    revokePhotoPreviewUrl,
    resolvePhotoFile,
    rotateImageFile,
} from '../photo-utils';
import type { UploadedPhoto } from '../types';
import {
    ACCEPTED_IMAGE_TYPES,
    MAX_FILE_SIZE,
    MAX_FILE_SIZE_MB,
    MAX_PHOTOS,
} from '../validation';

interface PropertyPhotoGridProps {
    photos: UploadedPhoto[];
    onChange: Dispatch<SetStateAction<UploadedPhoto[]>>;
    uploadLabel?: string;
    addLabel?: string;
}

interface SortablePhotoItemProps {
    photo: UploadedPhoto;
    index: number;
    onRotate: (index: number) => void;
    onRemove: (id: string) => void;
}

function SortablePhotoItem({ photo, index, onRotate, onRemove }: SortablePhotoItemProps) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({
        id: photo.id,
        disabled: photo.uploading,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={cn(
                'group relative aspect-[4/3] touch-none overflow-hidden rounded-xl',
                !photo.uploading && 'cursor-grab active:cursor-grabbing',
                isDragging && 'z-10 opacity-60 shadow-lg ring-2 ring-primary ring-offset-2',
            )}
            {...attributes}
            {...listeners}
        >
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
                src={photo.url}
                alt={`Фото ${index + 1}`}
                className="pointer-events-none h-full w-full object-cover"
                draggable={false}
            />

            {photo.uploading && (
                <div className="absolute inset-0 flex items-center justify-center bg-foreground/30">
                    <Loader2 className="h-6 w-6 animate-spin text-white" />
                </div>
            )}

            {index === 0 && !photo.uploading && (
                <span className="pointer-events-none absolute left-2 top-2 rounded-md bg-primary px-2 py-0.5 text-xs font-semibold text-primary-foreground">
                    Обложка
                </span>
            )}

            {!photo.uploading && (
                <button
                    type="button"
                    onPointerDown={(event) => event.stopPropagation()}
                    onClick={() => onRotate(index)}
                    className="absolute bottom-2 right-2 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full bg-card/80 text-foreground opacity-100 backdrop-blur-sm transition-opacity sm:opacity-0 sm:group-hover:opacity-100"
                    title="Повернуть на 90°"
                >
                    <RotateCw className="h-3.5 w-3.5" />
                </button>
            )}

            <button
                type="button"
                onPointerDown={(event) => event.stopPropagation()}
                onClick={() => onRemove(photo.id)}
                className="absolute right-2 top-2 cursor-pointer rounded-full bg-card/80 p-1.5 text-destructive opacity-100 backdrop-blur-sm transition-opacity sm:opacity-0 sm:group-hover:opacity-100"
            >
                <X className="h-4 w-4" />
            </button>
        </div>
    );
}

export function PropertyPhotoGrid({
    photos,
    onChange,
    uploadLabel = 'Загрузить фото',
    addLabel = 'Добавить',
}: PropertyPhotoGridProps) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [dragOver, setDragOver] = useState(false);

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: { distance: 8 },
        }),
        useSensor(TouchSensor, {
            activationConstraint: { delay: 200, tolerance: 8 },
        }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const updatePhotoById = useCallback(
        (id: string, updater: (photo: UploadedPhoto) => UploadedPhoto) => {
            onChange((current) => current.map((photo) => (photo.id === id ? updater(photo) : photo)));
        },
        [onChange],
    );

    const removePhotoById = useCallback(
        (id: string) => {
            onChange((current) => {
                const photo = current.find((item) => item.id === id);
                if (photo) {
                    revokePhotoPreviewUrl(photo);
                }
                return current.filter((item) => item.id !== id);
            });
        },
        [onChange],
    );

    const uploadPhotoFile = useCallback(
        async (id: string, file: File) => {
            try {
                const serverUrl = await uploadFile(file);
                updatePhotoById(id, (photo) => {
                    revokePhotoPreviewUrl(photo);
                    return {
                        ...photo,
                        url: serverUrl,
                        file,
                        uploading: false,
                    };
                });
            } catch (error) {
                const message = error instanceof FileTooLargeError
                    ? `${file.name}: файл слишком большой (макс. ${MAX_FILE_SIZE_MB} МБ)`
                    : `Не удалось загрузить фото ${file.name}`;
                toast.error(message);
                removePhotoById(id);
            }
        },
        [removePhotoById, updatePhotoById],
    );

    const handleFiles = useCallback(
        async (files: FileList | File[]) => {
            const arr = Array.from(files);
            const remaining = MAX_PHOTOS - photos.length;
            if (arr.length > remaining) {
                toast.error(`Можно добавить ещё ${remaining} фото`);
            }

            const batch = arr.slice(0, remaining);
            const validFiles = batch.filter((file) => {
                if (!(ACCEPTED_IMAGE_TYPES as readonly string[]).includes(file.type)) {
                    toast.error(`${file.name}: допустимы только JPEG, PNG и WebP`);
                    return false;
                }
                if (file.size > MAX_FILE_SIZE) {
                    toast.error(`${file.name}: максимум ${MAX_FILE_SIZE_MB} МБ`);
                    return false;
                }
                return true;
            });

            if (!validFiles.length) {
                return;
            }

            const placeholders = validFiles.map((file) => createUploadedPhoto(file));
            onChange((current) => [...current, ...placeholders]);

            for (const placeholder of placeholders) {
                if (placeholder.file) {
                    await uploadPhotoFile(placeholder.id, placeholder.file);
                }
            }
        },
        [onChange, photos.length, uploadPhotoFile],
    );

    const rotatePhoto = useCallback(
        async (index: number) => {
            const photo = photos[index];
            if (!photo || photo.uploading) {
                return;
            }

            updatePhotoById(photo.id, (current) => ({ ...current, uploading: true }));

            try {
                const sourceFile = await resolvePhotoFile(photo);
                const rotatedFile = await rotateImageFile(sourceFile, 90);
                const previewUrl = URL.createObjectURL(rotatedFile);

                updatePhotoById(photo.id, (current) => {
                    revokePhotoPreviewUrl(current);
                    return {
                        ...current,
                        url: previewUrl,
                        file: rotatedFile,
                        uploading: true,
                    };
                });

                await uploadPhotoFile(photo.id, rotatedFile);
            } catch {
                toast.error('Не удалось повернуть фото');
                updatePhotoById(photo.id, (current) => ({ ...current, uploading: false }));
            }
        },
        [photos, updatePhotoById, uploadPhotoFile],
    );

    const handleSortDragEnd = useCallback(
        (event: DragEndEvent) => {
            const { active, over } = event;
            if (!over || active.id === over.id) {
                return;
            }

            onChange((current) => {
                const oldIndex = current.findIndex((photo) => photo.id === active.id);
                const newIndex = current.findIndex((photo) => photo.id === over.id);
                if (oldIndex < 0 || newIndex < 0) {
                    return current;
                }
                return arrayMove(current, oldIndex, newIndex);
            });
        },
        [onChange],
    );

    const handleGridDragOver = (event: React.DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        if (event.dataTransfer.types.includes('Files')) {
            setDragOver(true);
        }
    };

    const handleGridDrop = (event: React.DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setDragOver(false);
        if (event.dataTransfer.files?.length) {
            void handleFiles(event.dataTransfer.files);
        }
    };

    return (
        <>
            <input
                ref={fileInputRef}
                type="file"
                accept="image/jpeg,image/png,image/webp"
                multiple
                onChange={(event) => {
                    if (event.target.files?.length) {
                        void handleFiles(event.target.files);
                        event.target.value = '';
                    }
                }}
                className="hidden"
            />

            <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragEnd={handleSortDragEnd}
            >
                <SortableContext
                    items={photos.map((photo) => photo.id)}
                    strategy={rectSortingStrategy}
                >
                    <div
                        className={cn(
                            'grid grid-cols-2 gap-3 sm:grid-cols-3',
                            dragOver && 'rounded-xl ring-2 ring-primary ring-offset-2',
                        )}
                        onDragOver={handleGridDragOver}
                        onDragLeave={() => setDragOver(false)}
                        onDrop={handleGridDrop}
                    >
                        {photos.map((photo, index) => (
                            <SortablePhotoItem
                                key={photo.id}
                                photo={photo}
                                index={index}
                                onRotate={rotatePhoto}
                                onRemove={removePhotoById}
                            />
                        ))}

                        {photos.length < MAX_PHOTOS && (
                            <button
                                type="button"
                                onClick={() => fileInputRef.current?.click()}
                                className={cn(
                                    'group flex aspect-[4/3] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed transition-colors',
                                    dragOver
                                        ? 'border-primary bg-primary/5 text-primary'
                                        : 'border-border bg-surface text-muted-foreground hover:border-primary/40 hover:text-primary',
                                )}
                            >
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-muted transition-colors group-hover:bg-primary/10">
                                    <Upload className="h-5 w-5 text-muted-foreground transition-colors group-hover:text-primary" />
                                </div>
                                <span className="text-xs font-medium text-muted-foreground">
                                    {dragOver ? 'Отпустите файлы' : photos.length === 0 ? uploadLabel : addLabel}
                                </span>
                            </button>
                        )}
                    </div>
                </SortableContext>
            </DndContext>
        </>
    );
}
