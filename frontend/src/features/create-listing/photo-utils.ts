import type { UploadedPhoto } from './types';

export const createUploadedPhoto = (file: File, uploading = true): UploadedPhoto => ({
    id: crypto.randomUUID(),
    url: URL.createObjectURL(file),
    file,
    uploading,
});

export const createUploadedPhotoFromUrl = (url: string): UploadedPhoto => ({
    id: crypto.randomUUID(),
    url,
});

export const revokePhotoPreviewUrl = (photo: UploadedPhoto): void => {
    if (photo.url.startsWith('blob:')) {
        URL.revokeObjectURL(photo.url);
    }
};

export async function resolvePhotoFile(photo: UploadedPhoto): Promise<File> {
    if (photo.file) {
        return photo.file;
    }

    const response = await fetch(photo.url);
    if (!response.ok) {
        throw new Error('Не удалось загрузить изображение');
    }

    const blob = await response.blob();
    const extension = blob.type === 'image/png'
        ? 'png'
        : blob.type === 'image/webp'
            ? 'webp'
            : 'jpg';

    return new File([blob], `photo.${extension}`, { type: blob.type || 'image/jpeg' });
}

export async function rotateImageFile(file: File, degrees = 90): Promise<File> {
    const bitmap = await createImageBitmap(file);
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');

    if (!context) {
        bitmap.close();
        throw new Error('Не удалось обработать изображение');
    }

    const radians = (degrees * Math.PI) / 180;
    const swapDimensions = degrees % 180 !== 0;

    canvas.width = swapDimensions ? bitmap.height : bitmap.width;
    canvas.height = swapDimensions ? bitmap.width : bitmap.height;

    context.translate(canvas.width / 2, canvas.height / 2);
    context.rotate(radians);
    context.drawImage(bitmap, -bitmap.width / 2, -bitmap.height / 2);
    bitmap.close();

    const outputType = file.type || 'image/jpeg';
    const blob = await new Promise<Blob>((resolve, reject) => {
        canvas.toBlob(
            (result) => (result ? resolve(result) : reject(new Error('Не удалось сохранить изображение'))),
            outputType,
            0.92,
        );
    });

    return new File([blob], file.name, { type: outputType });
}
