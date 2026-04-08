'use client';

import { useParams, useRouter } from 'next/navigation';
import { useState, useEffect, useRef, useCallback } from 'react';
import { motion } from 'framer-motion';
import { toast } from 'sonner';
import {
    ArrowLeft,
    Save,
    Loader2,
    BedDouble,
    Bath,
    Maximize,
    Calendar,
    Search,
    MapPin,
    Upload,
    X,
    Info,
} from 'lucide-react';
import Link from 'next/link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AddressMapPicker from '@/components/AddressMapPicker';
import { loadYmaps } from '@/lib/ymaps';
import { useProperty, useUpdateProperty } from '@/features/properties/hooks';
import type { UpdatePropertyPayload } from '@/features/properties/api';
import type { Property as PropertyItem } from '@/features/properties/types';
import { useSearchCities, useSearchStreets } from '@/features/create-listing/hooks';
import { uploadFile, FileTooLargeError } from '@/features/create-listing/api';
import {
    balconyOptions,
    dealConditionOptions,
    sanitizeDealConditionsForPropertyType,
    roomsRequired,
    renovationOptionsForDeal,
    showBalcony,
    showBathrooms,
    showFloor,
    showKitchenArea,
    showLivingArea,
    showRenovation,
    showRoomDealFields,
    showRooms,
    showTotalFloors,
    showYearBuilt,
} from '@/features/create-listing/property-field-rules';
import {
    ACCEPTED_IMAGE_TYPES,
    AREA_MAX,
    AREA_MIN,
    BATHROOMS_MAX,
    BATHROOMS_MIN,
    DESCRIPTION_MAX_LENGTH,
    DESCRIPTION_MIN_LENGTH,
    FLOOR_MAX,
    FLOOR_MIN,
    MAX_FILE_SIZE,
    MAX_PHOTOS,
    ROOMS_MAX,
    ROOMS_MIN,
    TITLE_MAX_LENGTH,
    TITLE_MIN_LENGTH,
    TOTAL_FLOORS_MAX,
    TOTAL_FLOORS_MIN,
    YEAR_BUILT_MAX,
    YEAR_BUILT_MIN,
    getDescriptionFieldError,
    getTitleFieldError,
    isNumberInRange,
} from '@/features/create-listing/validation';
import type { CitySearchResult } from '@/features/create-listing/types';
import { useDebouncedValue } from '@/hooks/use-debounced-value';

const propertyTypes = [
    { value: 'apartment', label: 'Квартира' },
    { value: 'house', label: 'Дом / Коттедж' },
    { value: 'room', label: 'Комната' },
    { value: 'land', label: 'Участок' },
    { value: 'garage', label: 'Гараж' },
    { value: 'parking', label: 'Машиноместо' },
    { value: 'dacha', label: 'Дача' },
    { value: 'office', label: 'Офис' },
    { value: 'retail', label: 'Торговое помещение' },
    { value: 'warehouse', label: 'Склад' },
];

const dealTypes = [
    { value: 'sale', label: 'Продажа' },
    { value: 'rent', label: 'Аренда' },
    { value: 'daily', label: 'Посуточно' },
];
const dailyKinds = ['apartment', 'house', 'dacha'];
const lotAreaTypes = ['land', 'house', 'dacha'];

const requiresAreaInSquareMeters = (propertyType: string): boolean => propertyType !== 'land';
const needsLotArea = (propertyType: string): boolean => lotAreaTypes.includes(propertyType);

const DEFAULT_CENTER: [number, number] = [53.9045, 27.5615];

interface EditFormData {
    type: string;
    dealType: string;
    title: string;
    description: string;
    rooms: string;
    roomsInDeal: string;
    roomsArea: string;
    bathrooms: string;
    area: string;
    landArea: string;
    livingArea: string;
    kitchenArea: string;
    maxDailyGuests: string;
    dailyBedCount: string;
    checkInTime: string;
    checkOutTime: string;
    floor: string;
    totalFloors: string;
    yearBuilt: string;
    renovation: string;
    balcony: string;
    dealConditions: string[];
    price: string;
    currency: string;
    cityId: number | null;
    cityName: string;
    streetName: string;
    streetId: number | null;
    building: string;
    block: string;
    latitude: number | null;
    longitude: number | null;
    images: { url: string; uploading?: boolean; file?: File }[];
    contactPhone: string;
    contactName: string;
}

type EditTitleDescriptionErrors = Partial<Pick<EditFormData, 'title' | 'description'>>;

function getErrorMessage(error: unknown, fallback: string): string {
    if (typeof error !== 'object' || error === null || !('response' in error)) {
        return fallback;
    }

    const response = (error as { response?: { data?: unknown } }).response;
    const data = response?.data;
    if (typeof data !== 'object' || data === null) {
        return fallback;
    }

    const message = (data as { message?: unknown }).message;
    return typeof message === 'string' && message.length > 0 ? message : fallback;
}

function mapPropertyToForm(property: PropertyItem): EditFormData {
    const revisionData = property.pendingRevisionStatus ? property.pendingRevisionData : null;
    const type = revisionData?.type ?? property.type;
    const rawDealConditions = revisionData?.dealConditions ?? property.specifications.dealConditions ?? [];

    return {
        type,
        dealType: revisionData?.dealType ?? property.dealType,
        title: revisionData?.title ?? property.title,
        description: revisionData?.description ?? property.description,
        rooms: String(revisionData?.rooms ?? property.specifications.rooms),
        roomsInDeal:
            revisionData?.roomsInDeal != null
                ? String(revisionData.roomsInDeal)
                : property.specifications.roomsInDeal != null
                  ? String(property.specifications.roomsInDeal)
                  : '',
        roomsArea:
            revisionData?.roomsArea != null
                ? String(revisionData.roomsArea)
                : property.specifications.roomsArea != null
                  ? String(property.specifications.roomsArea)
                  : '',
        bathrooms: String(revisionData?.bathrooms ?? property.specifications.bathrooms ?? ''),
        area: String(revisionData?.area ?? property.specifications.area),
        landArea: revisionData?.landArea
            ? String(revisionData.landArea)
            : property.specifications.landArea
                ? String(property.specifications.landArea)
                : '',
        livingArea: revisionData?.livingArea
            ? String(revisionData.livingArea)
            : property.specifications.livingArea
                ? String(property.specifications.livingArea)
                : '',
        kitchenArea: revisionData?.kitchenArea
            ? String(revisionData.kitchenArea)
            : property.specifications.kitchenArea
                ? String(property.specifications.kitchenArea)
                : '',
        maxDailyGuests: revisionData?.maxDailyGuests
            ? String(revisionData.maxDailyGuests)
            : property.specifications.maxDailyGuests
                ? String(property.specifications.maxDailyGuests)
                : '',
        dailyBedCount: revisionData?.dailyBedCount
            ? String(revisionData.dailyBedCount)
            : property.specifications.dailyBedCount
                ? String(property.specifications.dailyBedCount)
                : '',
        checkInTime: revisionData?.checkInTime ?? property.specifications.checkInTime ?? '',
        checkOutTime: revisionData?.checkOutTime ?? property.specifications.checkOutTime ?? '',
        floor: String(revisionData?.floor ?? property.specifications.floor ?? ''),
        totalFloors: String(revisionData?.totalFloors ?? property.specifications.totalFloors ?? ''),
        yearBuilt: String(revisionData?.yearBuilt ?? property.specifications.yearBuilt ?? ''),
        renovation: revisionData?.renovation ?? property.specifications.renovation ?? '',
        balcony: revisionData?.balcony ?? property.specifications.balcony ?? '',
        dealConditions: sanitizeDealConditionsForPropertyType(type, rawDealConditions),
        price: String(revisionData?.priceAmount ?? property.price.amount),
        currency: revisionData?.priceCurrency ?? property.price.currency,
        cityId: revisionData?.cityId ?? property.address.cityId,
        cityName: property.address.cityName || '',
        streetName: property.address.streetName || '',
        streetId: revisionData?.streetId ?? property.address.streetId ?? null,
        building: revisionData?.building ?? property.address.building ?? '',
        block: revisionData?.block ?? property.address.block ?? '',
        latitude: revisionData?.latitude ?? property.coordinates?.latitude ?? null,
        longitude: revisionData?.longitude ?? property.coordinates?.longitude ?? null,
        images: revisionData?.images
            ? revisionData.images.map((url) => ({ url }))
            : property.images.map((img) => ({ url: img.url })),
        contactPhone: revisionData?.contactPhone ?? '',
        contactName: revisionData?.contactName ?? '',
    };
}

export default function EditPropertyPage() {
    const { id } = useParams<{ id: string }>();
    const propertyId = Number(id);
    const router = useRouter();
    const { data: property, isLoading } = useProperty(propertyId);
    const { mutateAsync: updateProperty, isPending: saving } = useUpdateProperty();

    const [form, setForm] = useState<EditFormData | null>(null);
    const [fieldErrors, setFieldErrors] = useState<EditTitleDescriptionErrors>({});
    const [geocoding, setGeocoding] = useState(false);
    const [dragOver, setDragOver] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [cityQuery, setCityQuery] = useState('');
    const [cityDropdownOpen, setCityDropdownOpen] = useState(false);
    const debouncedCityQuery = useDebouncedValue(cityQuery, 300);
    const { data: cityResults = [], isFetching: citySearching } = useSearchCities(debouncedCityQuery);
    const cityContainerRef = useRef<HTMLDivElement>(null);

    const [streetQuery, setStreetQuery] = useState('');
    const [streetDropdownOpen, setStreetDropdownOpen] = useState(false);
    const debouncedStreetQuery = useDebouncedValue(streetQuery, 300);
    const { data: streetResults = [], isFetching: streetSearching } = useSearchStreets(
        form?.cityId ?? null,
        debouncedStreetQuery,
    );
    const streetContainerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!property || form) return;
        setForm(mapPropertyToForm(property));
        setCityQuery(property.address.cityName || '');
        setStreetQuery(property.address.streetName || '');
    }, [property, form]);

    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (cityContainerRef.current && !cityContainerRef.current.contains(e.target as Node)) {
                setCityDropdownOpen(false);
            }
            if (streetContainerRef.current && !streetContainerRef.current.contains(e.target as Node)) {
                setStreetDropdownOpen(false);
            }
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    const update = useCallback((key: keyof EditFormData, value: EditFormData[keyof EditFormData]) => {
        if (key === 'title' || key === 'description') {
            setFieldErrors((prev) => {
                const next = { ...prev };
                delete next[key];
                return next;
            });
        }
        setForm((prev) => {
            if (!prev) return prev;
            if (key === 'dealType' && value === 'daily') {
                return {
                    ...prev,
                    dealType: 'daily',
                    ...(prev.type === 'room' ? { type: '', roomsInDeal: '', roomsArea: '' } : {}),
                    ...(prev.renovation === 'Без ремонта' ? { renovation: '' } : {}),
                };
            }
            if (key === 'dealType' && value === 'rent' && prev.type === 'land') {
                return { ...prev, dealType: 'rent', type: '', roomsInDeal: '', roomsArea: '' };
            }
            if (key === 'dealType' || key === 'type') {
                const nextType = key === 'type' ? (value as string) : prev.type;
                return {
                    ...prev,
                    [key]: value as string,
                    roomsInDeal: '',
                    roomsArea: '',
                    dealConditions: sanitizeDealConditionsForPropertyType(nextType, prev.dealConditions),
                };
            }
            return { ...prev, [key]: value };
        });
    }, []);

    const handleTitleBlur = useCallback(() => {
        setFieldErrors((prev) => {
            const next = { ...prev };
            const err = getTitleFieldError(form?.title ?? '');
            if (err) next.title = err;
            else delete next.title;
            return next;
        });
    }, [form?.title]);

    const handleDescriptionBlur = useCallback(() => {
        setFieldErrors((prev) => {
            const next = { ...prev };
            const err = getDescriptionFieldError(form?.description ?? '');
            if (err) next.description = err;
            else delete next.description;
            return next;
        });
    }, [form?.description]);

    const toggleDealCondition = useCallback((condition: string) => {
        setForm((prev) => {
            if (!prev) return prev;
            const exists = prev.dealConditions.includes(condition);
            const dealConditions = exists ? [] : [condition];
            return { ...prev, dealConditions };
        });
    }, []);

    const selectCity = useCallback((city: CitySearchResult) => {
        setForm((prev) =>
            prev
                ? {
                      ...prev,
                      cityId: city.id,
                      cityName: city.name,
                      streetName: '',
                      streetId: null,
                      latitude: city.latitude ? Number(city.latitude) : prev.latitude,
                      longitude: city.longitude ? Number(city.longitude) : prev.longitude,
                  }
                : prev,
        );
        setCityQuery(city.name);
        setCityDropdownOpen(false);
        setStreetQuery('');
    }, []);

    const geocodeAddress = async () => {
        if (!form) return;
        const parts = [form.cityName, form.streetName, form.building, form.block].filter(Boolean);
        const query = parts.join(', ');
        if (!query) return;

        setGeocoding(true);
        try {
            const ymaps = await loadYmaps();
            const result = await ymaps.geocode(query, { results: 1 });
            const first = result.geoObjects.get(0);
            if (first) {
                const coords = first.geometry.getCoordinates();
                setForm((prev) => (prev ? { ...prev, latitude: coords[0], longitude: coords[1] } : prev));
            } else {
                toast.error('Не удалось определить координаты по адресу');
            }
        } catch {
            toast.error('Ошибка геокодирования');
        } finally {
            setGeocoding(false);
        }
    };

    const handleFiles = async (files: FileList | File[]) => {
        if (!form) return;
        const arr = Array.from(files);
        const remaining = MAX_PHOTOS - form.images.length;
        if (arr.length > remaining) {
            toast.error(`Можно добавить ещё ${remaining} фото`);
        }
        const batch = arr.slice(0, remaining);
        const validFiles = batch.filter((f) => {
            if (!ACCEPTED_IMAGE_TYPES.includes(f.type as (typeof ACCEPTED_IMAGE_TYPES)[number])) {
                toast.error(`${f.name}: допустимы только JPEG, PNG и WebP`);
                return false;
            }
            if (f.size > MAX_FILE_SIZE) {
                toast.error(`${f.name}: максимум 10 МБ`);
                return false;
            }
            return true;
        });
        if (!validFiles.length) return;

        const placeholders = validFiles.map((f) => ({
            url: URL.createObjectURL(f),
            file: f,
            uploading: true,
        }));

        setForm((prev) => (prev ? { ...prev, images: [...prev.images, ...placeholders] } : prev));

        for (let i = 0; i < validFiles.length; i++) {
            try {
                const serverUrl = await uploadFile(validFiles[i]);
                setForm((prev) => {
                    if (!prev) return prev;
                    const images = [...prev.images];
                    const idx = prev.images.length - validFiles.length + i;
                    if (images[idx]) {
                        images[idx] = { ...images[idx], url: serverUrl, uploading: false };
                    }
                    return { ...prev, images };
                });
            } catch (err) {
                const message =
                    err instanceof FileTooLargeError
                        ? `${validFiles[i].name}: файл слишком большой (макс. 10 МБ)`
                        : `Не удалось загрузить фото ${validFiles[i].name}`;
                toast.error(message);
                setForm((prev) => {
                    if (!prev) return prev;
                    const images = [...prev.images];
                    const idx = prev.images.length - validFiles.length + i;
                    if (images[idx]?.uploading) images.splice(idx, 1);
                    return { ...prev, images };
                });
            }
        }
    };

    const removePhoto = (index: number) => {
        setForm((prev) => (prev ? { ...prev, images: prev.images.filter((_, i) => i !== index) } : prev));
    };

    const handleSubmit = async () => {
        if (!form || !Number.isFinite(propertyId) || propertyId <= 0) return;
        if (property?.pendingRevisionStatus === 'pending') {
            toast.error('Изменения уже отправлены на модерацию');
            return;
        }

        const title = form.title.trim();
        const description = form.description.trim();
        const area = Number(form.area);
        const landArea = Number(form.landArea);
        const rooms = Number(form.rooms);
        const bathrooms = Number(form.bathrooms);
        const floor = Number(form.floor);
        const totalFloors = Number(form.totalFloors);
        const yearBuilt = Number(form.yearBuilt);
        const livingArea = Number(form.livingArea);
        const kitchenArea = Number(form.kitchenArea);
        const maxDailyGuests = Number(form.maxDailyGuests);
        const dailyBedCount = Number(form.dailyBedCount);
        const price = Number(form.price);

        const titleValidationError = getTitleFieldError(form.title);
        if (titleValidationError) {
            setFieldErrors((prev) => ({ ...prev, title: titleValidationError }));
            toast.error(titleValidationError);
            return;
        }
        const descriptionValidationError = getDescriptionFieldError(form.description);
        if (descriptionValidationError) {
            setFieldErrors((prev) => ({ ...prev, description: descriptionValidationError }));
            toast.error(descriptionValidationError);
            return;
        }
        if (requiresAreaInSquareMeters(form.type)) {
            if (!form.area || !isNumberInRange(area, AREA_MIN, AREA_MAX)) {
                toast.error(`Площадь общая: от ${AREA_MIN} до ${AREA_MAX} м²`);
                return;
            }
        }
        if (needsLotArea(form.type) && (!form.landArea || !Number.isFinite(landArea) || landArea <= 0)) {
            toast.error('Площадь участка должна быть положительной');
            return;
        }
        if (showRooms(form.type) && roomsRequired(form.type) && !form.rooms) {
            toast.error('Укажите количество комнат');
            return;
        }
        if (showRooms(form.type) && form.rooms && !isNumberInRange(rooms, ROOMS_MIN, ROOMS_MAX)) {
            toast.error(`Количество комнат должно быть от ${ROOMS_MIN} до ${ROOMS_MAX}`);
            return;
        }
        if (showRoomDealFields(form.type, form.dealType)) {
            if (!form.roomsInDeal.trim()) {
                toast.error('Укажите количество комнат в сделке');
                return;
            }
            const rid = Number(form.roomsInDeal);
            if (!isNumberInRange(rid, ROOMS_MIN, ROOMS_MAX)) {
                toast.error(`Комнат в сделке: от ${ROOMS_MIN} до ${ROOMS_MAX}`);
                return;
            }
            if (!form.roomsArea.trim()) {
                toast.error('Укажите площадь комнат в сделке');
                return;
            }
            const ra = Number(form.roomsArea);
            if (!isNumberInRange(ra, AREA_MIN, AREA_MAX)) {
                toast.error(`Площадь комнат в сделке: от ${AREA_MIN} до ${AREA_MAX} м²`);
                return;
            }
        }
        if (form.dealType === 'daily' && form.type === 'room') {
            toast.error('Посуточная сдача комнат недоступна');
            return;
        }
        if (
            showBathrooms(form.type)
            && form.bathrooms
            && !isNumberInRange(bathrooms, BATHROOMS_MIN, BATHROOMS_MAX)
        ) {
            toast.error(`Санузлов должно быть от ${BATHROOMS_MIN} до ${BATHROOMS_MAX}`);
            return;
        }
        if (showFloor(form.type) && form.floor && !isNumberInRange(floor, FLOOR_MIN, FLOOR_MAX)) {
            toast.error(`Этаж должен быть от ${FLOOR_MIN} до ${FLOOR_MAX}`);
            return;
        }
        if (
            showTotalFloors(form.type)
            && form.totalFloors
            && !isNumberInRange(totalFloors, TOTAL_FLOORS_MIN, TOTAL_FLOORS_MAX)
        ) {
            toast.error(`Этажей должно быть от ${TOTAL_FLOORS_MIN} до ${TOTAL_FLOORS_MAX}`);
            return;
        }
        if (
            showFloor(form.type)
            && showTotalFloors(form.type)
            && form.floor
            && form.totalFloors
            && isNumberInRange(floor, FLOOR_MIN, FLOOR_MAX)
            && isNumberInRange(totalFloors, TOTAL_FLOORS_MIN, TOTAL_FLOORS_MAX)
            && floor > totalFloors
        ) {
            toast.error('Этаж не может быть больше чем этажей в доме');
            return;
        }
        if (
            showYearBuilt(form.type)
            && form.yearBuilt
            && !isNumberInRange(yearBuilt, YEAR_BUILT_MIN, YEAR_BUILT_MAX)
        ) {
            toast.error(`Год постройки должен быть от ${YEAR_BUILT_MIN} до ${YEAR_BUILT_MAX}`);
            return;
        }
        if (showLivingArea(form.type) && form.livingArea && (!Number.isFinite(livingArea) || livingArea <= 0)) {
            toast.error('Жилая площадь должна быть положительной');
            return;
        }
        if (showKitchenArea(form.type) && form.kitchenArea && (!Number.isFinite(kitchenArea) || kitchenArea <= 0)) {
            toast.error('Площадь кухни должна быть положительной');
            return;
        }
        if (form.dealType === 'daily') {
            if (!form.maxDailyGuests || !Number.isFinite(maxDailyGuests) || maxDailyGuests <= 0) {
                toast.error('Укажите максимальное число гостей');
                return;
            }
            if (!form.dailyBedCount || !Number.isFinite(dailyBedCount) || dailyBedCount <= 0) {
                toast.error('Укажите количество кроватей');
                return;
            }
            if (form.checkInTime && !/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(form.checkInTime)) {
                toast.error('Время заезда должно быть в формате ЧЧ:ММ');
                return;
            }
            if (form.checkOutTime && !/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(form.checkOutTime)) {
                toast.error('Время выезда должно быть в формате ЧЧ:ММ');
                return;
            }
        }
        if (!form.price || !Number.isFinite(price) || price <= 0) {
            toast.error('Укажите цену');
            return;
        }

        const hasUploading = form.images.some((img) => img.uploading);
        if (hasUploading) {
            toast.error('Дождитесь загрузки всех фото');
            return;
        }

        try {
            const payload: UpdatePropertyPayload = {
                type: form.type,
                dealType: form.dealType,
                title,
                description,
                price: { amount: Math.round(price), currency: form.currency },
                area: requiresAreaInSquareMeters(form.type) ? area : 0,
                landArea: needsLotArea(form.type) && form.landArea
                    ? landArea
                    : undefined,
                rooms: showRooms(form.type) ? (rooms || 1) : undefined,
                bathrooms: showBathrooms(form.type) && form.bathrooms !== ''
                    ? bathrooms
                    : undefined,
                floor: showFloor(form.type) ? (Number.isFinite(floor) ? floor : undefined) : undefined,
                totalFloors: showTotalFloors(form.type)
                    ? (Number.isFinite(totalFloors) ? totalFloors : undefined)
                    : undefined,
                yearBuilt: showYearBuilt(form.type) && form.yearBuilt
                    ? yearBuilt
                    : undefined,
                renovation: showRenovation(form.type) ? (form.renovation || undefined) : undefined,
                balcony: showBalcony(form.type) ? (form.balcony || undefined) : undefined,
                livingArea: showLivingArea(form.type) && form.livingArea ? Number(form.livingArea) : undefined,
                kitchenArea: showKitchenArea(form.type) && form.kitchenArea ? Number(form.kitchenArea) : undefined,
                roomsInDeal: showRoomDealFields(form.type, form.dealType) && form.roomsInDeal !== ''
                    ? Number(form.roomsInDeal)
                    : undefined,
                roomsArea: showRoomDealFields(form.type, form.dealType) && form.roomsArea !== ''
                    ? Number(form.roomsArea)
                    : undefined,
                dealConditions: form.dealType !== 'daily' && form.dealConditions.length ? form.dealConditions : undefined,
                maxDailyGuests: form.dealType === 'daily' && form.maxDailyGuests ? Number(form.maxDailyGuests) : undefined,
                dailyBedCount: form.dealType === 'daily' && form.dailyBedCount ? Number(form.dailyBedCount) : undefined,
                checkInTime: form.dealType === 'daily' && form.checkInTime ? form.checkInTime : undefined,
                checkOutTime: form.dealType === 'daily' && form.checkOutTime ? form.checkOutTime : undefined,
                building: form.building.trim(),
                block: form.block.trim() || undefined,
                cityId: form.cityId ?? undefined,
                streetId: form.streetId,
                coordinates:
                    form.latitude !== null && form.longitude !== null
                        ? { latitude: form.latitude, longitude: form.longitude }
                        : undefined,
                images: form.images.filter((img) => !img.uploading).map((img) => img.url),
            };
            await updateProperty({ id: propertyId, data: payload });
            toast.success(
                property?.status === 'published' || property?.status === 'rejected'
                    ? 'Изменения отправлены на модерацию'
                    : 'Объявление обновлено'
            );
            router.push('/kabinet/moi-obyavleniya/aktivnye');
        } catch (err: unknown) {
            toast.error(getErrorMessage(err, 'Не удалось сохранить изменения'));
        }
    };

    const formatCityLabel = (city: CitySearchResult) => {
        const parts: string[] = [city.name];
        if (city.districtName) parts.push(city.districtName + ' р-н');
        if (city.regionName) parts.push(city.regionName + ' обл.');
        if (city.ruralCouncil) parts.push(city.ruralCouncil + ' с/с');
        return parts;
    };

    if (isLoading || !form) {
        return (
            <div className="flex items-center justify-center py-24">
                <Loader2 className="w-8 h-8 animate-spin text-primary" />
            </div>
        );
    }

    const mapCenter: [number, number] =
        form.latitude !== null && form.longitude !== null
            ? [form.latitude, form.longitude]
            : DEFAULT_CENTER;
    const availablePropertyTypes =
        form.dealType === 'daily'
            ? propertyTypes.filter((pt) => dailyKinds.includes(pt.value))
            : form.dealType === 'rent'
              ? propertyTypes.filter(
                    (pt) => pt.value !== 'land' || form.type === 'land',
                )
              : propertyTypes;
    const revisionOnModeration = property?.pendingRevisionStatus === 'pending';
    const revisionRejected = property?.pendingRevisionStatus === 'rejected';
    const formReadonly = revisionOnModeration || saving;

    return (
        <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3 }}>
            <div className="flex items-center gap-3 mb-6">
                <Button variant="ghost" size="icon" asChild>
                    <Link href="/kabinet/moi-obyavleniya/aktivnye/">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                </Button>
                <div>
                    <h1 className="text-2xl font-display font-bold text-foreground">Редактирование объявления</h1>
                    <p className="text-sm text-muted-foreground mt-0.5">Внесите изменения и сохраните</p>
                </div>
            </div>

            <div className="space-y-6 max-w-3xl">
                {revisionOnModeration && (
                    <div className="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        Изменения уже отправлены на модерацию. Пока проверка не завершена, редактирование недоступно.
                    </div>
                )}
                {revisionRejected && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Последняя ревизия отклонена.
                        {property?.pendingRevisionComment ? ` Комментарий: ${property.pendingRevisionComment}` : ''}
                    </div>
                )}
                <fieldset disabled={formReadonly} className="space-y-6">
                {/* Type & Deal */}
                <section className="bg-card rounded-2xl shadow-card border border-border p-6">
                    <h2 className="text-lg font-semibold text-foreground mb-4">Тип объявления</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <Label className="text-foreground">Тип сделки</Label>
                            <Select value={form.dealType} onValueChange={(v) => update('dealType', v)}>
                                <SelectTrigger className="mt-1.5 px-2.5">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {dealTypes.map((dt) => (
                                        <SelectItem key={dt.value} value={dt.value}>
                                            {dt.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label className="text-foreground">Тип недвижимости</Label>
                            <Select value={form.type} onValueChange={(v) => update('type', v)}>
                                <SelectTrigger className="mt-1.5">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {availablePropertyTypes.map((pt) => (
                                        <SelectItem key={pt.value} value={pt.value}>
                                            {pt.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </section>

                {/* Details */}
                <section className="bg-card rounded-2xl shadow-card border border-border p-6">
                    <h2 className="text-lg font-semibold text-foreground mb-4">Описание объекта</h2>
                    <div className="space-y-4 [&_label]:min-h-5 [&_label]:flex [&_label]:items-center [&_label]:gap-1.5">
                        <div>
                            <Label htmlFor="title" className="text-foreground">
                                Заголовок *{' '}
                                <span className="font-normal text-muted-foreground/80">
                                    ({TITLE_MIN_LENGTH}–{TITLE_MAX_LENGTH} символов)
                                </span>
                            </Label>
                            <Input
                                id="title"
                                value={form.title}
                                onChange={(e) => update('title', e.target.value)}
                                onBlur={handleTitleBlur}
                                className={`mt-1.5 ${fieldErrors.title ? 'border-destructive' : ''}`}
                                maxLength={TITLE_MAX_LENGTH}
                                aria-invalid={fieldErrors.title ? true : undefined}
                            />
                            <div className="flex items-start justify-between gap-2 mt-1">
                                {fieldErrors.title ? (
                                    <p className="text-xs text-destructive">{fieldErrors.title}</p>
                                ) : (
                                    <span />
                                )}
                                <span className="text-xs text-muted-foreground/70 ml-auto shrink-0 tabular-nums">
                                    {form.title.length}/{TITLE_MAX_LENGTH}
                                </span>
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="description" className="text-foreground">
                                Описание *{' '}
                                <span className="font-normal text-muted-foreground/80">
                                    ({DESCRIPTION_MIN_LENGTH}–{DESCRIPTION_MAX_LENGTH} символов)
                                </span>
                            </Label>
                            <Textarea
                                id="description"
                                value={form.description}
                                onChange={(e) => update('description', e.target.value)}
                                onBlur={handleDescriptionBlur}
                                rows={5}
                                className={`mt-1.5 resize-none ${fieldErrors.description ? 'border-destructive' : ''}`}
                                maxLength={DESCRIPTION_MAX_LENGTH}
                                aria-invalid={fieldErrors.description ? true : undefined}
                            />
                            <div className="flex items-start justify-between gap-2 mt-1">
                                {fieldErrors.description ? (
                                    <p className="text-xs text-destructive">{fieldErrors.description}</p>
                                ) : (
                                    <span />
                                )}
                                <span className="text-xs text-muted-foreground/70 ml-auto shrink-0 tabular-nums">
                                    {form.description.length}/{DESCRIPTION_MAX_LENGTH}
                                </span>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            {showRooms(form.type) && (
                                <div>
                                    <Label className="text-foreground flex items-center gap-1.5">
                                        <BedDouble className="w-3.5 h-3.5" /> Комнат
                                    </Label>
                                    <Input
                                        type="number"
                                        min={ROOMS_MIN}
                                        max={ROOMS_MAX}
                                        value={form.rooms}
                                        onChange={(e) => update('rooms', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                            )}
                            {showRoomDealFields(form.type, form.dealType) && (
                                <>
                                    <div>
                                        <Label className="text-foreground flex items-center gap-1.5">
                                            <BedDouble className="w-3.5 h-3.5" /> Комнат в сделке *
                                        </Label>
                                        <Input
                                            type="number"
                                            min={ROOMS_MIN}
                                            max={ROOMS_MAX}
                                            value={form.roomsInDeal}
                                            onChange={(e) => update('roomsInDeal', e.target.value)}
                                            className="mt-1.5"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-foreground flex items-center gap-1.5">
                                            <Maximize className="w-3.5 h-3.5" /> Площадь комнат в сделке, м² *
                                        </Label>
                                        <Input
                                            type="number"
                                            min={AREA_MIN}
                                            max={AREA_MAX}
                                            step={0.1}
                                            value={form.roomsArea}
                                            onChange={(e) => update('roomsArea', e.target.value)}
                                            className="mt-1.5"
                                        />
                                    </div>
                                </>
                            )}
                            {showBathrooms(form.type) && (
                                <div>
                                    <Label className="text-foreground flex items-center gap-1.5">
                                        <Bath className="w-3.5 h-3.5" /> Санузлов
                                    </Label>
                                    <Input
                                        type="number"
                                        min={BATHROOMS_MIN}
                                        max={BATHROOMS_MAX}
                                        value={form.bathrooms}
                                        onChange={(e) => update('bathrooms', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                            )}
                            {requiresAreaInSquareMeters(form.type) && (
                                <div>
                                    <Label className="text-foreground flex items-center gap-1.5">
                                        <Maximize className="w-3.5 h-3.5" /> Площадь общая, м² *
                                    </Label>
                                    <Input
                                        type="number"
                                        min={AREA_MIN}
                                        max={AREA_MAX}
                                        value={form.area}
                                        onChange={(e) => update('area', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                            )}
                            {needsLotArea(form.type) && (
                                <div>
                                    <Label className="text-foreground flex items-center gap-1.5">
                                        <MapPin className="w-3.5 h-3.5" /> Площадь участка, соток *
                                    </Label>
                                    <Input
                                        type="number"
                                        min={0.01}
                                        step={0.01}
                                        value={form.landArea}
                                        onChange={(e) => update('landArea', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                            )}
                            {showFloor(form.type) && (
                                <div>
                                    <Label className="text-foreground">Этаж</Label>
                                    <Input
                                        type="number"
                                        min={FLOOR_MIN}
                                        max={FLOOR_MAX}
                                        value={form.floor}
                                        onChange={(e) => update('floor', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                            )}
                            {showTotalFloors(form.type) && (
                                <div>
                                    <Label className="text-foreground">Этажей в доме</Label>
                                    <Input
                                        type="number"
                                        min={TOTAL_FLOORS_MIN}
                                        max={TOTAL_FLOORS_MAX}
                                        value={form.totalFloors}
                                        onChange={(e) => update('totalFloors', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                            )}
                            {showYearBuilt(form.type) && (
                                <div>
                                    <Label className="text-foreground flex items-center gap-1.5">
                                        <Calendar className="w-3.5 h-3.5" /> Год постройки
                                    </Label>
                                    <Input
                                        type="number"
                                        min={YEAR_BUILT_MIN}
                                        max={YEAR_BUILT_MAX}
                                        value={form.yearBuilt}
                                        onChange={(e) => update('yearBuilt', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                            )}
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {showLivingArea(form.type) && (
                                <div>
                                    <Label className="text-foreground">Площадь жилая, м²</Label>
                                    <Input
                                        type="number"
                                        min={1}
                                        value={form.livingArea}
                                        onChange={(e) => update('livingArea', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                            )}
                            {showKitchenArea(form.type) && (
                                <div>
                                    <Label className="text-foreground">Площадь кухни, м²</Label>
                                    <Input
                                        type="number"
                                        min={1}
                                        value={form.kitchenArea}
                                        onChange={(e) => update('kitchenArea', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                            )}
                        </div>

                        {form.dealType === 'daily' && (
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-foreground">Максимальное число гостей *</Label>
                                    <Input
                                        type="number"
                                        min={1}
                                        value={form.maxDailyGuests}
                                        onChange={(e) => update('maxDailyGuests', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                                <div>
                                    <Label className="text-foreground">Количество кроватей *</Label>
                                    <Input
                                        type="number"
                                        min={1}
                                        value={form.dailyBedCount}
                                        onChange={(e) => update('dailyBedCount', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                                <div>
                                    <Label className="text-foreground">Время заезда</Label>
                                    <Input
                                        type="time"
                                        value={form.checkInTime}
                                        onChange={(e) => update('checkInTime', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                                <div>
                                    <Label className="text-foreground">Время выезда</Label>
                                    <Input
                                        type="time"
                                        value={form.checkOutTime}
                                        onChange={(e) => update('checkOutTime', e.target.value)}
                                        className="mt-1.5"
                                    />
                                </div>
                            </div>
                        )}

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {showRenovation(form.type) && (
                                <div>
                                    <Label className="text-foreground">Ремонт</Label>
                                    <Select value={form.renovation || '__none'} onValueChange={(v) => update('renovation', v === '__none' ? '' : v)}>
                                        <SelectTrigger className="mt-1.5">
                                            <SelectValue placeholder="Выберите вариант" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__none">Не указано</SelectItem>
                                            {renovationOptionsForDeal(form.dealType).map((opt) => (
                                                <SelectItem key={opt} value={opt}>{opt}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            {showBalcony(form.type) && (
                                <div>
                                    <Label className="text-foreground">Балкон / Лоджия</Label>
                                    <Select value={form.balcony || '__none'} onValueChange={(v) => update('balcony', v === '__none' ? '' : v)}>
                                        <SelectTrigger className="mt-1.5">
                                            <SelectValue placeholder="Выберите вариант" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__none">Не указано</SelectItem>
                                            {balconyOptions.map((opt) => (
                                                <SelectItem key={opt} value={opt}>{opt}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                        </div>

                        {form.dealType !== 'daily' && (
                            <div>
                                <Label className="text-foreground">Условия сделки</Label>
                                <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2">
                                    {dealConditionOptions(form.dealType, form.type).map((option) => {
                                        const selected = form.dealConditions.includes(option);
                                        return (
                                            <button
                                                key={option}
                                                onClick={() => toggleDealCondition(option)}
                                                className={`px-3 py-2 rounded-lg text-sm border transition-all ${selected
                                                    ? 'border-primary bg-accent text-foreground'
                                                    : 'border-border text-muted-foreground hover:border-primary/30 hover:text-foreground'
                                                    }`}
                                            >
                                                {option}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </div>
                </section>

                {/* Photos */}
                <section className="bg-card rounded-2xl shadow-card border border-border p-6">
                    <h2 className="text-lg font-semibold text-foreground mb-4">Фотографии</h2>
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        onChange={(e) => {
                            if (e.target.files?.length) {
                                handleFiles(e.target.files);
                                e.target.value = '';
                            }
                        }}
                        className="hidden"
                    />
                    <div
                        className={`grid grid-cols-2 sm:grid-cols-3 gap-3 ${dragOver ? 'ring-2 ring-primary ring-offset-2 rounded-xl' : ''}`}
                        onDragOver={(e) => {
                            e.preventDefault();
                            setDragOver(true);
                        }}
                        onDragLeave={() => setDragOver(false)}
                        onDrop={(e) => {
                            e.preventDefault();
                            setDragOver(false);
                            if (e.dataTransfer.files?.length) handleFiles(e.dataTransfer.files);
                        }}
                    >
                        {form.images.map((photo, i) => (
                            <div key={i} className="relative aspect-[4/3] rounded-xl overflow-hidden group">
                                {/* eslint-disable-next-line @next/next/no-img-element */}
                                <img src={photo.url} alt={`Фото ${i + 1}`} className="w-full h-full object-cover" />
                                {photo.uploading && (
                                    <div className="absolute inset-0 bg-foreground/30 flex items-center justify-center">
                                        <Loader2 className="w-6 h-6 text-white animate-spin" />
                                    </div>
                                )}
                                {i === 0 && !photo.uploading && (
                                    <span className="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-primary text-primary-foreground text-xs font-medium">
                                        Обложка
                                    </span>
                                )}
                                <button
                                    onClick={() => removePhoto(i)}
                                    className="absolute top-2 right-2 w-7 h-7 rounded-full bg-foreground/60 text-background flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <X className="w-3.5 h-3.5" />
                                </button>
                            </div>
                        ))}
                        {form.images.length < MAX_PHOTOS && (
                            <button
                                onClick={() => fileInputRef.current?.click()}
                                className={`aspect-[4/3] rounded-xl border-2 border-dashed flex flex-col items-center justify-center gap-2 transition-colors ${
                                    dragOver
                                        ? 'border-primary bg-primary/5 text-primary'
                                        : 'border-border hover:border-primary/40 text-muted-foreground hover:text-primary'
                                }`}
                            >
                                <Upload className="w-6 h-6" />
                                <span className="text-xs font-medium">Добавить</span>
                            </button>
                        )}
                    </div>
                </section>

                {/* Address */}
                <section className="bg-card rounded-2xl shadow-card border border-border p-6">
                    <h2 className="text-lg font-semibold text-foreground mb-4">Расположение</h2>
                    <div className="space-y-4">
                        <div ref={cityContainerRef} className="relative">
                            <Label className="text-foreground">Город *</Label>
                            <div className="relative mt-1.5">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                                <Input
                                    value={cityQuery}
                                    onChange={(e) => {
                                        setCityQuery(e.target.value);
                                        setCityDropdownOpen(true);
                                        if (!e.target.value.trim()) {
                                            setForm((prev) =>
                                                prev
                                                    ? { ...prev, cityId: null, cityName: '', streetName: '', streetId: null }
                                                    : prev,
                                            );
                                            setStreetQuery('');
                                        }
                                    }}
                                    onFocus={() => {
                                        if (cityQuery.length >= 2) setCityDropdownOpen(true);
                                    }}
                                    placeholder="Начните вводить название города..."
                                    className="pl-9"
                                />
                                {citySearching && (
                                    <Loader2 className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 animate-spin text-muted-foreground" />
                                )}
                            </div>
                            {cityDropdownOpen && cityResults.length > 0 && (
                                <div className="absolute z-50 w-full mt-1 bg-popover border border-border rounded-xl shadow-lg max-h-60 overflow-auto">
                                    {cityResults.map((city) => {
                                        const parts = formatCityLabel(city);
                                        return (
                                            <button
                                                key={city.id}
                                                onClick={() => selectCity(city)}
                                                className="w-full text-left px-4 py-2.5 hover:bg-accent transition-colors first:rounded-t-xl last:rounded-b-xl"
                                            >
                                                <span className="font-medium text-foreground">{parts[0]}</span>
                                                {parts.length > 1 && (
                                                    <span className="text-sm text-muted-foreground ml-2">
                                                        {parts.slice(1).join(', ')}
                                                    </span>
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        <div ref={streetContainerRef} className="relative">
                            <Label className="text-foreground">Улица</Label>
                            <div className="relative mt-1.5">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                                <Input
                                    value={streetQuery}
                                    onChange={(e) => {
                                        setStreetQuery(e.target.value);
                                        setStreetDropdownOpen(true);
                                        setForm((prev) =>
                                            prev ? { ...prev, streetName: e.target.value, streetId: null } : prev,
                                        );
                                    }}
                                    onFocus={() => {
                                        if (form.cityId && streetQuery.length >= 1) setStreetDropdownOpen(true);
                                    }}
                                    placeholder={form.cityId ? 'Начните вводить название улицы...' : 'Сначала выберите город'}
                                    disabled={!form.cityId}
                                    className="pl-9"
                                />
                                {streetSearching && (
                                    <Loader2 className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 animate-spin text-muted-foreground" />
                                )}
                            </div>
                            {streetDropdownOpen && streetResults.length > 0 && (
                                <div className="absolute z-50 w-full mt-1 bg-popover border border-border rounded-xl shadow-lg max-h-60 overflow-auto">
                                    {streetResults.map((street) => (
                                        <button
                                            key={street.id}
                                            onClick={() => {
                                                const displayName = street.type
                                                    ? `${street.type} ${street.name}`
                                                    : street.name;
                                                setStreetQuery(displayName);
                                                setForm((prev) =>
                                                    prev
                                                        ? { ...prev, streetName: displayName, streetId: street.id }
                                                        : prev,
                                                );
                                                setStreetDropdownOpen(false);
                                            }}
                                            className="w-full text-left px-4 py-2.5 hover:bg-accent transition-colors first:rounded-t-xl last:rounded-b-xl"
                                        >
                                            {street.type && (
                                                <span className="text-sm text-muted-foreground mr-1">{street.type}</span>
                                            )}
                                            <span className="font-medium text-foreground">{street.name}</span>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <Label className="text-foreground">Номер дома</Label>
                                <Input
                                    value={form.building}
                                    onChange={(e) => update('building', e.target.value)}
                                    placeholder="Например: 58 (необязательно)"
                                    className="mt-1.5"
                                />
                            </div>
                            <div>
                                <Label className="text-foreground">Корпус</Label>
                                <Input
                                    value={form.block}
                                    onChange={(e) => update('block', e.target.value)}
                                    placeholder="Например: 2 (необязательно)"
                                    className="mt-1.5"
                                />
                            </div>
                            <div className="flex items-end">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={geocodeAddress}
                                    disabled={(
                                        !form.streetName.trim()
                                        && !form.building.trim()
                                        && !form.block.trim()
                                    ) || geocoding}
                                    className="gap-2 w-full"
                                >
                                    {geocoding ? (
                                        <Loader2 className="w-4 h-4 animate-spin" />
                                    ) : (
                                        <MapPin className="w-4 h-4" />
                                    )}
                                    Найти на карте
                                </Button>
                            </div>
                        </div>

                        <AddressMapPicker
                            center={mapCenter}
                            latitude={form.latitude}
                            longitude={form.longitude}
                            onCoordsChange={(lat, lng) =>
                                setForm((prev) => (prev ? { ...prev, latitude: lat, longitude: lng } : prev))
                            }
                        />
                    </div>
                </section>

                {/* Price */}
                <section className="bg-card rounded-2xl shadow-card border border-border p-6">
                    <h2 className="text-lg font-semibold text-foreground mb-4">Стоимость</h2>
                    <div className="grid grid-cols-3 gap-3">
                        <div className="col-span-2">
                            <Label className="text-foreground">Цена *</Label>
                            <Input
                                type="number"
                                min={0}
                                value={form.price}
                                onChange={(e) => update('price', e.target.value)}
                                className="mt-1.5"
                            />
                        </div>
                        <div>
                            <Label className="text-foreground">Валюта</Label>
                            <Select value={form.currency} onValueChange={(v) => update('currency', v)}>
                                <SelectTrigger className="mt-1.5">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="USD">$</SelectItem>
                                    <SelectItem value="BYN">BYN</SelectItem>
                                    <SelectItem value="EUR">€</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </section>
                </fieldset>

                {/* Actions */}
                <div className="flex items-center justify-between pt-2 pb-8">
                    <Button variant="ghost" asChild className="gap-2 text-muted-foreground">
                        <Link href="/kabinet/moi-obyavleniya/aktivnye/">
                            <ArrowLeft className="w-4 h-4" />
                            Отмена
                        </Link>
                    </Button>
                    <Button
                        onClick={handleSubmit}
                        disabled={saving || revisionOnModeration}
                        className="gap-2 bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0"
                    >
                        {revisionOnModeration ? (
                            <>
                                <Info className="w-4 h-4" />
                                Изменения на модерации
                            </>
                        ) : saving ? (
                            <>
                                <Loader2 className="w-4 h-4 animate-spin" />
                                Сохранение...
                            </>
                        ) : (
                            <>
                                <Save className="w-4 h-4" />
                                Сохранить
                            </>
                        )}
                    </Button>
                </div>
            </div>
        </motion.div>
    );
}
