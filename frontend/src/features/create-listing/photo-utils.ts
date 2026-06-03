import type { UploadedPhoto } from './types';

/** Stable local id for dnd-kit; works on HTTP where crypto.randomUUID is unavailable. */
function createPhotoId(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }
    if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
        const bytes = new Uint8Array(16);
        crypto.getRandomValues(bytes);
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;
        const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
        return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
    }
    return `photo-${Date.now()}-${Math.random().toString(36).slice(2, 11)}`;
}

export const createUploadedPhoto = (file: File, uploading = true): UploadedPhoto => ({
    id: createPhotoId(),
    url: URL.createObjectURL(file),
    file,
    uploading,
});

export const createUploadedPhotoFromUrl = (url: string): UploadedPhoto => ({
    id: createPhotoId(),
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

/** Читает EXIF Orientation (1–8) из JPEG; для остальных файлов возвращает 1. */
async function readExifOrientation(file: File): Promise<number> {
    if (file.type !== 'image/jpeg') {
        return 1;
    }

    const buffer = await file.slice(0, 256 * 1024).arrayBuffer();
    const view = new DataView(buffer);

    if (view.byteLength < 2 || view.getUint16(0) !== 0xffd8) {
        return 1;
    }

    let offset = 2;
    while (offset + 4 < view.byteLength) {
        if (view.getUint8(offset) !== 0xff) {
            break;
        }

        const marker = view.getUint8(offset + 1);
        if (marker === 0xe1) {
            const exifOffset = offset + 4;
            if (
                exifOffset + 6 <= view.byteLength
                && view.getUint32(exifOffset) === 0x45786966
                && view.getUint16(exifOffset + 4) === 0
            ) {
                const tiffOffset = exifOffset + 6;
                if (tiffOffset + 8 > view.byteLength) {
                    return 1;
                }

                const littleEndian = view.getUint16(tiffOffset) === 0x4949;
                const getUint16 = (pos: number) => view.getUint16(pos, littleEndian);
                const getUint32 = (pos: number) => view.getUint32(pos, littleEndian);

                const ifd0Offset = tiffOffset + getUint32(tiffOffset + 4);
                if (ifd0Offset + 2 > view.byteLength) {
                    return 1;
                }

                const entries = getUint16(ifd0Offset);
                for (let i = 0; i < entries; i++) {
                    const entryOffset = ifd0Offset + 2 + i * 12;
                    if (entryOffset + 12 > view.byteLength) {
                        break;
                    }
                    if (getUint16(entryOffset) === 0x0112) {
                        return getUint16(entryOffset + 8) || 1;
                    }
                }
            }
            return 1;
        }

        if (marker === 0xda || marker === 0xd9) {
            break;
        }

        const size = view.getUint16(offset + 2);
        if (size < 2) {
            break;
        }
        offset += 2 + size;
    }

    return 1;
}

async function bitmapToFile(bitmap: ImageBitmap, file: File): Promise<File> {
    const canvas = document.createElement('canvas');
    canvas.width = bitmap.width;
    canvas.height = bitmap.height;

    const context = canvas.getContext('2d');
    if (!context) {
        bitmap.close();
        throw new Error('Не удалось обработать изображение');
    }

    context.drawImage(bitmap, 0, 0);
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

/**
 * Применяет EXIF-ориентацию к пикселям файла.
 * Без этого вертикальные снимки с телефона часто отображаются и сохраняются «лёжа».
 */
export async function normalizeImageFile(file: File): Promise<File> {
    const orientation = await readExifOrientation(file);
    if (orientation === 1) {
        return file;
    }

    const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
    return bitmapToFile(bitmap, file);
}

export async function rotateImageFile(file: File, degrees = 90): Promise<File> {
    const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
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
