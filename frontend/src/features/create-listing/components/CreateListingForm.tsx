'use client';

import { useState, useRef, useCallback, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { motion, AnimatePresence } from 'framer-motion';
import { toast } from 'sonner';
import {
    ArrowLeft,
    Check,
    Upload,
    X,
    Home,
    MapPin,
    BedDouble,
    Bath,
    Maximize,
    Building2,
    Calendar,
    Loader2,
    Image as ImageIcon,
    Users,
    FileText,
    Sparkles,
    Wallet,
    type LucideIcon,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import AddressMapPicker from '@/components/AddressMapPicker';
import { loadYmaps } from '@/lib/ymaps';
import { useSearchCities, useSearchStreets, useCreateProperty } from '../hooks';
import { uploadFile, FileTooLargeError } from '../api';
import type { ListingFormData, UploadedPhoto, CreatePropertyPayload, CitySearchResult } from '../types';
import { LISTING_AMENITY_GROUPS } from '../listing-amenity-groups';
import {
    balconyOptions,
    dealConditionOptions,
    sanitizeDealConditionsForPropertyType,
    renovationOptionsForDeal,
    roomsRequired,
    showBalcony,
    showBathrooms,
    showDealConditions,
    showFloor,
    showKitchenArea,
    showLivingArea,
    showRenovation,
    showRoomDealFields,
    showRooms,
    showTotalFloors,
    showYearBuilt,
} from '../property-field-rules';
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
    DAILY_BEDS_MAX,
    TITLE_MAX_LENGTH,
    TITLE_MIN_LENGTH,
    TOTAL_FLOORS_MAX,
    TOTAL_FLOORS_MIN,
    YEAR_BUILT_MAX,
    YEAR_BUILT_MIN,
    getDescriptionFieldError,
    getTitleFieldError,
    isFloorNotAboveTotalFloors,
} from '../validation';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { isAuthenticated } from '@/lib/auth';
import { PhoneAuthModal } from '@/features/sms-auth/components/PhoneAuthModal';
import {
    applyBathroomTypeSelection,
    bathroomTypeFromForm,
    BathroomTypeRow,
    FloorTotalFloorsRow,
    NumericPillRow,
    SegmentedAreaTripleRow,
} from './listing-parameter-controls';

const LISTING_STEPS: readonly { label: string; Icon: LucideIcon }[] = [
    { label: 'Тип объекта', Icon: Building2 },
    { label: 'Основная информация', Icon: FileText },
    { label: 'Удобства', Icon: Sparkles },
    { label: 'Фотографии', Icon: ImageIcon },
    { label: 'Расположение', Icon: MapPin },
    { label: 'Условия Размещения', Icon: Wallet },
];

const TOTAL_STEPS = LISTING_STEPS.length;

const inputClass =
    'w-full px-4 py-2.5 rounded-xl bg-surface border border-border text-sm text-foreground placeholder:text-muted-foreground/60 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all';
const labelClass = 'text-sm font-semibold text-foreground mb-2 block font-display';

const pillBtnBase = 'px-3 py-1.5 rounded-lg text-sm font-medium transition-all';
const pillBtnInactiveLg =
    'flex w-full items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium border border-border bg-surface text-foreground hover:bg-muted transition-all text-left';
const pillBtnActiveLg =
    'flex w-full items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium border border-primary bg-primary text-primary-foreground hover:bg-primary/90 transition-all text-left';
const chipInactive = `${pillBtnBase} bg-surface border border-border text-foreground hover:bg-muted`;
const chipActive = `${pillBtnBase} bg-primary text-primary-foreground border border-primary`;
/** Чипы удобств: фон ближе к макету (светло-серый неактивный). */
const amenityChipInactive = `${pillBtnBase} bg-muted/70 border border-transparent text-foreground hover:bg-muted`;
const amenityChipActive = `${pillBtnBase} bg-primary text-primary-foreground border border-primary`;

/** Сайт только для посуточной сдачи: квартира или дом. */
const DAILY_PROPERTY_TYPE_VALUES = ['apartment', 'house'] as const;

const dailyPropertyChoices: { value: (typeof DAILY_PROPERTY_TYPE_VALUES)[number]; label: string; icon: typeof Building2 }[] = [
    { value: 'apartment', label: 'Квартира', icon: Building2 },
    { value: 'house', label: 'Дом', icon: Home },
];

const isAllowedDailyPropertyType = (t: string): boolean =>
    (DAILY_PROPERTY_TYPE_VALUES as readonly string[]).includes(t);

const lotAreaTypes = ['land', 'house', 'dacha'];

const requiresAreaInSquareMeters = (propertyType: string): boolean => propertyType !== 'land';
const needsLotArea = (propertyType: string): boolean => lotAreaTypes.includes(propertyType);

const titlePlaceholderByType: Record<string, string> = {
    apartment: 'Например: Уютная квартира на сутки в центре',
    room: 'Например: Комната 18 м² в 3-комнатной квартире',
    house: 'Например: Дом на сутки с баней и участком',
    dacha: 'Например: Дача с баней и садом в 20 км от города',
    land: 'Например: Участок 10 соток под строительство дома',
    office: 'Например: Офис 75 м² в бизнес-центре класса B',
    retail: 'Например: Торговое помещение 45 м² на первой линии',
    warehouse: 'Например: Склад 300 м² с удобным подъездом для фур',
    garage: 'Например: Гараж 24 м² в ГСК с охраной',
    parking: 'Например: Машиноместо в крытом паркинге у лифта',
};

const defaultTitlePlaceholder = 'Например: Объект в хорошем районе';
const getTitlePlaceholder = (propertyType: string): string =>
    titlePlaceholderByType[propertyType] ?? defaultTitlePlaceholder;

const DEFAULT_CENTER: [number, number] = [53.9045, 27.5615];

type FormErrors = Partial<Record<keyof ListingFormData, string>>;

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

const INITIAL_FORM: ListingFormData = {
    dealType: 'daily',
    propertyCategory: 'residential',
    propertyType: '',
    title: '',
    description: '',
    rooms: '',
    roomsInDeal: '',
    roomsArea: '',
    bathrooms: '',
    area: '',
    landArea: '',
    livingArea: '',
    kitchenArea: '',
    floor: '',
    totalFloors: '',
    maxDailyGuests: '',
    dailySingleBeds: '0',
    dailyDoubleBeds: '0',
    checkInTime: '',
    checkOutTime: '',
    yearBuilt: '',
    renovation: '',
    balcony: '',
    dealConditions: [],
    photos: [],
    cityId: null,
    citySlug: '',
    cityName: '',
    streetName: '',
    streetId: null,
    building: '',
    block: '',
    latitude: null,
    longitude: null,
    price: '',
    currency: 'USD',
    amenities: [],
};

export function CreateListingForm() {
    const router = useRouter();
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [step, setStep] = useState(1);
    const [form, setForm] = useState<ListingFormData>(INITIAL_FORM);
    const [errors, setErrors] = useState<FormErrors>({});
    const [dragOver, setDragOver] = useState(false);
    const [geocoding, setGeocoding] = useState(false);

    // City autocomplete state
    const [cityQuery, setCityQuery] = useState('');
    const [cityDropdownOpen, setCityDropdownOpen] = useState(false);
    const debouncedCityQuery = useDebouncedValue(cityQuery, 300);
    const { data: cityResults = [], isFetching: citySearching } = useSearchCities(debouncedCityQuery);
    const cityContainerRef = useRef<HTMLDivElement>(null);

    // Street autocomplete state
    const [streetQuery, setStreetQuery] = useState('');
    const [streetDropdownOpen, setStreetDropdownOpen] = useState(false);
    const debouncedStreetQuery = useDebouncedValue(streetQuery, 300);
    const { data: streetResults = [], isFetching: streetSearching } = useSearchStreets(form.cityId, debouncedStreetQuery);
    const streetContainerRef = useRef<HTMLDivElement>(null);

    const { mutateAsync: createProperty, isPending: submitting } = useCreateProperty();
    const [smsAuthOpen, setSmsAuthOpen] = useState(false);
    const [submitted, setSubmitted] = useState(false);
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

    const update = useCallback((key: keyof ListingFormData, value: ListingFormData[keyof ListingFormData]) => {
        setForm((prev) => {
            if (key === 'propertyType') {
                const nextType = value as string;
                return {
                    ...prev,
                    propertyType: nextType,
                    roomsInDeal: '',
                    roomsArea: '',
                    dealConditions: sanitizeDealConditionsForPropertyType(nextType, prev.dealConditions),
                };
            }
            return { ...prev, [key]: value };
        });
        setErrors((prev) => {
            const next = { ...prev };
            if (next[key]) {
                delete next[key];
            }
            if (key === 'propertyType') {
                delete next.roomsInDeal;
                delete next.roomsArea;
            }
            return next;
        });
    }, []);

    const toggleDealCondition = useCallback((condition: string) => {
        setForm((prev) => {
            const exists = prev.dealConditions.includes(condition);
            const dealConditions = exists ? [] : [condition];
            return { ...prev, dealConditions };
        });
    }, []);

    const toggleAmenity = useCallback((id: string) => {
        setForm((prev) => {
            const has = prev.amenities.includes(id);
            return {
                ...prev,
                amenities: has ? prev.amenities.filter((a) => a !== id) : [...prev.amenities, id],
            };
        });
    }, []);

    const selectCity = useCallback((city: CitySearchResult) => {
        setForm((prev) => ({
            ...prev,
            cityId: city.id,
            citySlug: city.slug,
            cityName: city.name,
            streetName: '',
            streetId: null,
            latitude: city.latitude ? Number(city.latitude) : null,
            longitude: city.longitude ? Number(city.longitude) : null,
        }));
        setCityQuery(city.name);
        setCityDropdownOpen(false);
        setStreetQuery('');
        setErrors((prev) => {
            const next = { ...prev };
            delete next.citySlug;
            return next;
        });
    }, []);

    const validateStep = useCallback((s: number): boolean => {
        const errs: FormErrors = {};

        switch (s) {
            case 1:
                if (!form.propertyType) {
                    errs.propertyType = 'Выберите тип объекта';
                } else if (!isAllowedDailyPropertyType(form.propertyType)) {
                    errs.propertyType = 'Выберите квартиру или дом';
                }
                break;
            case 2: {
                const titleErr = getTitleFieldError(form.title);
                if (titleErr) errs.title = titleErr;
                const descriptionErr = getDescriptionFieldError(form.description);
                if (descriptionErr) errs.description = descriptionErr;
                if (showRooms(form.propertyType) && roomsRequired(form.propertyType) && !form.rooms) {
                    errs.rooms = 'Укажите количество';
                }
                if (showRooms(form.propertyType) && form.rooms) {
                    const rooms = Number(form.rooms);
                    if (!Number.isFinite(rooms) || rooms < ROOMS_MIN || rooms > ROOMS_MAX) {
                        errs.rooms = `Комнат должно быть от ${ROOMS_MIN} до ${ROOMS_MAX}`;
                    }
                }
                if (showRoomDealFields(form.propertyType, form.dealType)) {
                    if (!form.roomsInDeal.trim()) {
                        errs.roomsInDeal = 'Укажите количество';
                    } else {
                        const rid = Number(form.roomsInDeal);
                        if (!Number.isFinite(rid) || rid < ROOMS_MIN || rid > ROOMS_MAX) {
                            errs.roomsInDeal = `От ${ROOMS_MIN} до ${ROOMS_MAX}`;
                        }
                    }
                    if (!form.roomsArea.trim()) {
                        errs.roomsArea = 'Укажите площадь комнат в сделке';
                    } else {
                        const ra = Number(form.roomsArea);
                        if (!Number.isFinite(ra) || ra < AREA_MIN || ra > AREA_MAX) {
                            errs.roomsArea = `Площадь комнат в сделке: от ${AREA_MIN} до ${AREA_MAX} м²`;
                        }
                    }
                }
                if (showBathrooms(form.propertyType) && form.bathrooms) {
                    const bathrooms = Number(form.bathrooms);
                    if (!Number.isFinite(bathrooms) || bathrooms < BATHROOMS_MIN || bathrooms > BATHROOMS_MAX) {
                        errs.bathrooms = `Санузлов должно быть от ${BATHROOMS_MIN} до ${BATHROOMS_MAX}`;
                    }
                }
                if (requiresAreaInSquareMeters(form.propertyType)) {
                    if (!form.area) errs.area = 'Укажите общую площадь';
                    else if (Number(form.area) < AREA_MIN || Number(form.area) > AREA_MAX) {
                        errs.area = `Площадь общая: от ${AREA_MIN} до ${AREA_MAX} м²`;
                    }
                }
                if (needsLotArea(form.propertyType)) {
                    if (!form.landArea) errs.landArea = 'Укажите площадь участка';
                    else if (!Number.isFinite(Number(form.landArea)) || Number(form.landArea) <= 0) {
                        errs.landArea = 'Площадь участка должна быть положительной';
                    }
                }
                if (showLivingArea(form.propertyType) && form.livingArea && Number(form.livingArea) <= 0) {
                    errs.livingArea = 'Площадь должна быть положительной';
                }
                if (showKitchenArea(form.propertyType) && form.kitchenArea && Number(form.kitchenArea) <= 0) {
                    errs.kitchenArea = 'Площадь должна быть положительной';
                }
                if (showFloor(form.propertyType) && form.floor) {
                    const floor = Number(form.floor);
                    if (!Number.isFinite(floor) || floor < FLOOR_MIN || floor > FLOOR_MAX) {
                        errs.floor = `Этаж должен быть от ${FLOOR_MIN} до ${FLOOR_MAX}`;
                    }
                }
                if (showTotalFloors(form.propertyType) && form.totalFloors) {
                    const totalFloors = Number(form.totalFloors);
                    if (!Number.isFinite(totalFloors) || totalFloors < TOTAL_FLOORS_MIN || totalFloors > TOTAL_FLOORS_MAX) {
                        errs.totalFloors = `Этажей должно быть от ${TOTAL_FLOORS_MIN} до ${TOTAL_FLOORS_MAX}`;
                    }
                }
                if (
                    showFloor(form.propertyType)
                    && showTotalFloors(form.propertyType)
                    && form.floor
                    && form.totalFloors
                    && !errs.floor
                    && !errs.totalFloors
                ) {
                    const floorNum = Number(form.floor);
                    const totalFloorsNum = Number(form.totalFloors);
                    if (!isFloorNotAboveTotalFloors(floorNum, totalFloorsNum)) {
                        errs.floor = 'Этаж не может быть больше чем этажей в доме';
                    }
                }
                if (showYearBuilt(form.propertyType) && form.yearBuilt) {
                    const yearBuilt = Number(form.yearBuilt);
                    if (!Number.isFinite(yearBuilt) || yearBuilt < YEAR_BUILT_MIN || yearBuilt > YEAR_BUILT_MAX) {
                        errs.yearBuilt = `Год постройки должен быть от ${YEAR_BUILT_MIN} до ${YEAR_BUILT_MAX}`;
                    }
                }
                break;
            }
            case 3:
                break;
            case 4:
                break;
            case 5:
                if (!form.cityId) errs.citySlug = 'Выберите город';
                break;
            case 6:
                if (form.dealType === 'daily') {
                    if (!form.maxDailyGuests) {
                        errs.maxDailyGuests = 'Укажите максимум гостей';
                    } else if (!Number.isFinite(Number(form.maxDailyGuests)) || Number(form.maxDailyGuests) <= 0) {
                        errs.maxDailyGuests = 'Укажите корректное число гостей';
                    }
                    const singleBeds = Number(form.dailySingleBeds);
                    const doubleBeds = Number(form.dailyDoubleBeds);
                    if (!Number.isFinite(singleBeds) || singleBeds < 0 || singleBeds > DAILY_BEDS_MAX) {
                        errs.dailySingleBeds = `Односпальных: от 0 до ${DAILY_BEDS_MAX}`;
                    }
                    if (!Number.isFinite(doubleBeds) || doubleBeds < 0 || doubleBeds > DAILY_BEDS_MAX) {
                        errs.dailyDoubleBeds = `Двуспальных: от 0 до ${DAILY_BEDS_MAX}`;
                    }
                    if (
                        !errs.dailySingleBeds
                        && !errs.dailyDoubleBeds
                        && Number.isFinite(singleBeds)
                        && Number.isFinite(doubleBeds)
                        && singleBeds + doubleBeds < 1
                    ) {
                        errs.dailySingleBeds = 'Укажите хотя бы одну кровать';
                    }
                    if (form.checkInTime && !/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(form.checkInTime)) {
                        errs.checkInTime = 'Формат времени: ЧЧ:ММ';
                    }
                    if (form.checkOutTime && !/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(form.checkOutTime)) {
                        errs.checkOutTime = 'Формат времени: ЧЧ:ММ';
                    }
                }
                if (!form.price) errs.price = 'Укажите цену';
                else if (Number(form.price) <= 0) errs.price = 'Цена должна быть положительной';
                break;
        }

        setErrors(errs);
        return Object.keys(errs).length === 0;
    }, [form]);

    const canProceed = useCallback((): boolean => {
        switch (step) {
            case 1:
                return !!(form.propertyType && isAllowedDailyPropertyType(form.propertyType));
            case 2:
                return !!(
                    form.title.trim()
                    && form.title.trim().length >= TITLE_MIN_LENGTH
                    && form.title.trim().length <= TITLE_MAX_LENGTH
                    && form.description.trim().length >= DESCRIPTION_MIN_LENGTH
                    && form.description.trim().length <= DESCRIPTION_MAX_LENGTH
                    && (
                        !requiresAreaInSquareMeters(form.propertyType)
                        || (
                            !!form.area
                            && Number(form.area) >= AREA_MIN
                            && Number(form.area) <= AREA_MAX
                        )
                    )
                    && (
                        !needsLotArea(form.propertyType)
                        || (!!form.landArea && Number(form.landArea) > 0)
                    )
                    && (!showRooms(form.propertyType) || !roomsRequired(form.propertyType) || form.rooms)
                    && (!showRoomDealFields(form.propertyType, form.dealType) || (
                        !!form.roomsInDeal.trim()
                        && !!form.roomsArea.trim()
                        && Number.isFinite(Number(form.roomsInDeal))
                        && Number(form.roomsInDeal) >= ROOMS_MIN
                        && Number(form.roomsInDeal) <= ROOMS_MAX
                        && Number.isFinite(Number(form.roomsArea))
                        && Number(form.roomsArea) >= AREA_MIN
                        && Number(form.roomsArea) <= AREA_MAX
                    ))
                );
            case 3: return true;
            case 4: return true;
            case 5: return !!form.cityId;
            case 6:
                return !!(
                    form.price
                    && Number(form.price) > 0
                    && (form.dealType !== 'daily' || (
                        !!form.maxDailyGuests
                        && Number(form.maxDailyGuests) > 0
                        && Number(form.dailySingleBeds) >= 0
                        && Number(form.dailyDoubleBeds) >= 0
                        && Number(form.dailySingleBeds) + Number(form.dailyDoubleBeds) >= 1
                    ))
                );
            default: return false;
        }
    }, [step, form]);

    const next = () => { if (validateStep(step)) setStep((s) => Math.min(s + 1, TOTAL_STEPS)); };
    const prev = () => setStep((s) => Math.max(s - 1, 1));
    const goToStep = (target: number) => { if (target < step) setStep(target); };

    const resetListing = useCallback(() => {
        setForm({ ...INITIAL_FORM });
        setStep(1);
        setErrors({});
        setCityQuery('');
        setStreetQuery('');
        setCityDropdownOpen(false);
        setStreetDropdownOpen(false);
        setSubmitted(false);
    }, []);

    // --- File upload ---

    const handleFiles = async (files: FileList | File[]) => {
        const arr = Array.from(files);
        const remaining = MAX_PHOTOS - form.photos.length;
        if (arr.length > remaining) {
            toast.error(`Можно добавить ещё ${remaining} фото`);
        }
        const batch = arr.slice(0, remaining);

        const validFiles = batch.filter((f) => {
            if (!(ACCEPTED_IMAGE_TYPES as readonly string[]).includes(f.type)) {
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

        const placeholders: UploadedPhoto[] = validFiles.map((f) => ({
            url: URL.createObjectURL(f),
            file: f,
            uploading: true,
        }));

        setForm((prev) => ({
            ...prev,
            photos: [...prev.photos, ...placeholders],
        }));

        for (let i = 0; i < validFiles.length; i++) {
            try {
                const serverUrl = await uploadFile(validFiles[i]);
                setForm((prev) => {
                    const photos = [...prev.photos];
                    const idx = prev.photos.length - validFiles.length + i;
                    if (photos[idx]) {
                        photos[idx] = { ...photos[idx], url: serverUrl, uploading: false };
                    }
                    return { ...prev, photos };
                });
            } catch (err) {
                const message = err instanceof FileTooLargeError
                    ? `${validFiles[i].name}: файл слишком большой (макс. 10 МБ)`
                    : `Не удалось загрузить фото ${validFiles[i].name}`;
                toast.error(message);
                setForm((prev) => {
                    const photos = [...prev.photos];
                    const idx = prev.photos.length - validFiles.length + i;
                    if (photos[idx] && photos[idx].uploading) {
                        photos.splice(idx, 1);
                    }
                    return { ...prev, photos };
                });
            }
        }
    };

    const handleFileInput = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files?.length) {
            handleFiles(e.target.files);
            e.target.value = '';
        }
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setDragOver(false);
        if (e.dataTransfer.files?.length) handleFiles(e.dataTransfer.files);
    };

    const removePhoto = (index: number) => {
        setForm((prev) => ({ ...prev, photos: prev.photos.filter((_, i) => i !== index) }));
    };

    // --- Geocoding ---

    const geocodeAddress = useCallback(async () => {
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
                setForm((prev) => ({ ...prev, latitude: coords[0], longitude: coords[1] }));
            } else {
                toast.error('Не удалось определить координаты по адресу');
            }
        } catch {
            toast.error('Ошибка геокодирования');
        } finally {
            setGeocoding(false);
        }
    }, [form.cityName, form.streetName, form.building, form.block]);

    // --- Submit ---

    const handleSubmit = async () => {
        if (!validateStep(2) || !validateStep(6)) return;

        if (!isAuthenticated()) {
            setSmsAuthOpen(true);
            return;
        }

        await submitProperty();
    };

    const submitProperty = async () => {
        if (!form.cityId) { toast.error('Город не найден'); return; }

        const lat = form.latitude ?? DEFAULT_CENTER[0];
        const lng = form.longitude ?? DEFAULT_CENTER[1];

        const payload: CreatePropertyPayload = {
            type: form.propertyType,
            dealType: form.dealType,
            title: form.title.trim(),
            description: form.description.trim(),
            price: { amount: Math.round(Number(form.price)), currency: form.currency },
            area: requiresAreaInSquareMeters(form.propertyType) ? Number(form.area) : 0,
            landArea: needsLotArea(form.propertyType) && form.landArea
                ? Number(form.landArea)
                : undefined,
            rooms: showRooms(form.propertyType) && form.rooms !== ''
                ? Number(form.rooms)
                : undefined,
            floor: showFloor(form.propertyType) && form.floor !== ''
                ? Number(form.floor)
                : undefined,
            totalFloors: showTotalFloors(form.propertyType) && form.totalFloors !== ''
                ? Number(form.totalFloors)
                : undefined,
            bathrooms: showBathrooms(form.propertyType) && form.bathrooms !== ''
                ? Number(form.bathrooms)
                : undefined,
            yearBuilt: showYearBuilt(form.propertyType) && form.yearBuilt
                ? Number(form.yearBuilt)
                : undefined,
            renovation: showRenovation(form.propertyType) && form.renovation ? form.renovation : undefined,
            balcony: showBalcony(form.propertyType) && form.balcony ? form.balcony : undefined,
            livingArea: showLivingArea(form.propertyType) && form.livingArea
                ? Number(form.livingArea)
                : undefined,
            kitchenArea: showKitchenArea(form.propertyType) && form.kitchenArea
                ? Number(form.kitchenArea)
                : undefined,
            roomsInDeal: showRoomDealFields(form.propertyType, form.dealType) && form.roomsInDeal !== ''
                ? Number(form.roomsInDeal)
                : undefined,
            roomsArea: showRoomDealFields(form.propertyType, form.dealType) && form.roomsArea !== ''
                ? Number(form.roomsArea)
                : undefined,
            dealConditions: showDealConditions(form.dealType) && form.dealConditions.length > 0
                ? form.dealConditions
                : undefined,
            maxDailyGuests: form.dealType === 'daily' && form.maxDailyGuests
                ? Number(form.maxDailyGuests)
                : undefined,
            dailySingleBeds:
                form.dealType === 'daily' ? Number(form.dailySingleBeds) || 0 : undefined,
            dailyDoubleBeds:
                form.dealType === 'daily' ? Number(form.dailyDoubleBeds) || 0 : undefined,
            checkInTime: form.dealType === 'daily' && form.checkInTime
                ? form.checkInTime
                : undefined,
            checkOutTime: form.dealType === 'daily' && form.checkOutTime
                ? form.checkOutTime
                : undefined,
            building: form.building.trim(),
            block: form.block.trim() || undefined,
            cityId: form.cityId,
            streetId: form.streetId ?? undefined,
            coordinates: { latitude: lat, longitude: lng },
            images: form.photos.filter((p) => !p.uploading).map((p) => p.url),
            amenities: form.amenities,
        };

        try {
            await createProperty(payload);
            toast.success('Объявление отправлено на модерацию');
            setSubmitted(true);
        } catch (err: unknown) {
            const status = typeof err === 'object' && err !== null && 'response' in err
                ? (err as { response?: { status?: number } }).response?.status
                : undefined;

            if (status === 401) {
                toast.error('Для публикации необходимо войти в аккаунт', {
                    action: { label: 'Войти', onClick: () => router.push('/login') },
                });
            } else {
                toast.error(getErrorMessage(err, 'Не удалось создать объявление'));
            }
        }
    };

    const handleTitleBlur = useCallback(() => {
        setErrors((prev) => {
            const next = { ...prev };
            const err = getTitleFieldError(form.title);
            if (err) next.title = err;
            else delete next.title;
            return next;
        });
    }, [form.title]);

    const handleDescriptionBlur = useCallback(() => {
        setErrors((prev) => {
            const next = { ...prev };
            const err = getDescriptionFieldError(form.description);
            if (err) next.description = err;
            else delete next.description;
            return next;
        });
    }, [form.description]);

    const FieldError = ({ field }: { field: keyof FormErrors }) =>
        errors[field] ? <p className="text-xs text-destructive mt-1">{errors[field]}</p> : null;

    // --- City autocomplete display helper ---

    const formatCityLabel = (city: CitySearchResult) => {
        const parts: string[] = [city.name];
        if (city.districtName) parts.push(city.districtName + ' р-н');
        if (city.regionName) parts.push(city.regionName + ' обл.');
        if (city.ruralCouncil) parts.push(city.ruralCouncil + ' с/с');
        return parts;
    };

    // --- Map center ---

    const mapCenter: [number, number] = form.latitude !== null && form.longitude !== null
        ? [form.latitude, form.longitude]
        : DEFAULT_CENTER;

    if (submitted) {
        return (
            <div className="space-y-6 max-w-3xl">
                <div className="text-center py-16 space-y-4">
                    <div className="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center mx-auto">
                        <Check className="h-9 w-9 text-primary" />
                    </div>
                    <h2 className="font-display text-2xl font-bold text-foreground">Объявление создано!</h2>
                    <p className="text-muted-foreground max-w-md mx-auto text-sm">
                        Ваше объявление отправлено на модерацию. Обычно проверка занимает до 24 часов.
                    </p>
                    <div className="flex flex-wrap gap-3 justify-center pt-4">
                        <Button variant="outline" onClick={() => router.push('/kabinet')}>
                            К моим объявлениям
                        </Button>
                        <Button onClick={resetListing}>Создать ещё</Button>
                    </div>
                </div>

                <PhoneAuthModal
                    open={smsAuthOpen}
                    onOpenChange={setSmsAuthOpen}
                    initialPhone=""
                    onSuccess={() => {
                        void submitProperty();
                    }}
                />
            </div>
        );
    }

    return (
        <div className="space-y-6 max-w-3xl">
            <div className="flex items-center gap-3">
                <button
                    type="button"
                    onClick={() => (step > 1 ? prev() : router.back())}
                    className="p-2 rounded-lg hover:bg-muted transition-colors"
                >
                    <ArrowLeft className="h-5 w-5 text-muted-foreground" />
                </button>
                <div>
                    <h1 className="font-display text-2xl font-bold text-foreground">Новое объявление</h1>
                    <p className="text-sm text-muted-foreground">
                        Шаг {step} из {TOTAL_STEPS}
                        <span className="text-foreground font-medium"> — {LISTING_STEPS[step - 1].label}</span>
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-6 gap-1.5 sm:gap-3">
                {LISTING_STEPS.map(({ label, Icon }, i) => {
                    const segmentStep = i + 1;
                    const isPast = segmentStep < step;
                    const isCurrent = segmentStep === step;
                    const isUpcoming = segmentStep > step;
                    return (
                        <button
                            key={segmentStep}
                            type="button"
                            onClick={() => goToStep(segmentStep)}
                            disabled={isUpcoming}
                            title={label}
                            aria-current={isCurrent ? 'step' : undefined}
                            aria-label={`Шаг ${segmentStep}: ${label}`}
                            className={cn(
                                'flex min-h-[44px] min-w-0 sm:min-h-[4.25rem] flex-col items-center justify-center rounded-xl border-2 text-center transition-all outline-none',
                                'max-sm:px-1 max-sm:py-2 sm:px-2.5 sm:py-3 sm:gap-1.5',
                                'focus-visible:ring-2 focus-visible:ring-primary/35 focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                                isCurrent &&
                                    'border-primary bg-primary/10 text-foreground shadow-sm ring-1 ring-primary/15',
                                isPast &&
                                    'border-border bg-card text-foreground hover:border-primary/45 hover:bg-muted/40 active:scale-[0.98]',
                                isUpcoming &&
                                    'cursor-not-allowed border-dashed border-border/80 bg-muted/25 text-muted-foreground opacity-80',
                            )}
                        >
                            <Icon
                                className={cn(
                                    'h-5 w-5 shrink-0',
                                    isCurrent && 'text-primary',
                                    isUpcoming && 'text-muted-foreground',
                                )}
                                aria-hidden
                            />
                            <span
                                className={cn(
                                    'hidden min-w-0 sm:block text-[11px] sm:text-xs font-semibold leading-snug text-center w-full line-clamp-3 break-words',
                                    label === 'Расположение' && 'whitespace-nowrap',
                                )}
                            >
                                {label}
                            </span>
                        </button>
                    );
                })}
            </div>

            {/* Step content */}
            <AnimatePresence mode="wait">
                <motion.div
                    key={step}
                    initial={{ opacity: 0, x: 20 }}
                    animate={{ opacity: 1, x: 0 }}
                    exit={{ opacity: 0, x: -20 }}
                    transition={{ duration: 0.25 }}
                    className="space-y-6"
                >
                        {/* Step 1: только посуточно — квартира или дом */}
                        {step === 1 && (
                            <div className="bg-card rounded-2xl shadow-card p-6 space-y-5">
                                <div>
                                    <h2 className="font-display text-lg font-semibold text-foreground">Тип объекта</h2>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        Посуточная сдача — выберите квартиру или дом
                                    </p>
                                </div>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    {dailyPropertyChoices.map((choice) => {
                                        const Icon = choice.icon;
                                        return (
                                            <button
                                                key={choice.value}
                                                type="button"
                                                onClick={() => update('propertyType', choice.value)}
                                                className={form.propertyType === choice.value ? pillBtnActiveLg : pillBtnInactiveLg}
                                            >
                                                <Icon className="w-5 h-5 flex-shrink-0" />
                                                {choice.label}
                                            </button>
                                        );
                                    })}
                                </div>
                                <FieldError field="propertyType" />
                            </div>
                        )}

                        {/* Step 2: Details */}
                        {step === 2 && (
                            <>
                                <div className="bg-card rounded-2xl shadow-card p-6 space-y-5">
                                    <h2 className="font-display text-lg font-semibold text-foreground">Основная информация</h2>

                                    <div>
                                        <label htmlFor="title" className={labelClass}>
                                            Заголовок объявления *
                                        </label>
                                        <input
                                            id="title"
                                            value={form.title}
                                            onChange={(e) => update('title', e.target.value)}
                                            onBlur={handleTitleBlur}
                                            placeholder={getTitlePlaceholder(form.propertyType)}
                                            maxLength={TITLE_MAX_LENGTH}
                                            aria-invalid={errors.title ? true : undefined}
                                            className={cn(inputClass, errors.title ? 'border-destructive' : '')}
                                        />
                                        <p className="text-xs text-muted-foreground mt-1">
                                            {TITLE_MIN_LENGTH}–{TITLE_MAX_LENGTH} символов · {form.title.length}/{TITLE_MAX_LENGTH}
                                        </p>
                                        <FieldError field="title" />
                                    </div>

                                    <div>
                                        <label htmlFor="description" className={labelClass}>
                                            Описание *
                                        </label>
                                        <textarea
                                            id="description"
                                            value={form.description}
                                            onChange={(e) => update('description', e.target.value)}
                                            onBlur={handleDescriptionBlur}
                                            placeholder="Опишите преимущества вашего объекта, расположение, состояние ремонта, инфраструктуру рядом..."
                                            rows={5}
                                            maxLength={DESCRIPTION_MAX_LENGTH}
                                            aria-invalid={errors.description ? true : undefined}
                                            className={cn(inputClass, 'resize-none', errors.description ? 'border-destructive' : '')}
                                        />
                                        <p className="text-xs text-muted-foreground mt-1">
                                            {DESCRIPTION_MIN_LENGTH}–{DESCRIPTION_MAX_LENGTH} символов · {form.description.length}/{DESCRIPTION_MAX_LENGTH}
                                        </p>
                                        <FieldError field="description" />
                                    </div>
                                </div>

                                <div className="bg-card rounded-2xl shadow-card p-6 space-y-5">
                                    <h2 className="font-display text-lg font-semibold text-foreground">Параметры объекта</h2>

                                    <div className="flex flex-col gap-5">
                                        {showRooms(form.propertyType) && (
                                            <NumericPillRow
                                                label={
                                                    <>
                                                        <BedDouble className="w-3.5 h-3.5" /> Количество комнат
                                                        {roomsRequired(form.propertyType) ? (
                                                            <span className="text-destructive">*</span>
                                                        ) : null}
                                                    </>
                                                }
                                                value={form.rooms}
                                                onChange={(v) => update('rooms', v)}
                                                min={ROOMS_MIN}
                                                max={ROOMS_MAX}
                                                plusDiscrete
                                                error={errors.rooms}
                                            />
                                        )}
                                        {showRoomDealFields(form.propertyType, form.dealType) && (
                                            <>
                                                <NumericPillRow
                                                    label={
                                                        <>
                                                            <BedDouble className="w-3.5 h-3.5" /> Комнат в сделке *
                                                        </>
                                                    }
                                                    value={form.roomsInDeal}
                                                    onChange={(v) => update('roomsInDeal', v)}
                                                    min={ROOMS_MIN}
                                                    max={ROOMS_MAX}
                                                    plusDiscrete
                                                    error={errors.roomsInDeal}
                                                />
                                                <div>
                                                    <label className={labelClass}>
                                                        <Maximize className="w-3.5 h-3.5 inline mr-1 align-text-bottom" />
                                                        Площадь комнат в сделке, м² *
                                                    </label>
                                                    <input
                                                        type="number"
                                                        min={AREA_MIN}
                                                        max={AREA_MAX}
                                                        step={0.1}
                                                        value={form.roomsArea}
                                                        onChange={(e) => update('roomsArea', e.target.value)}
                                                        placeholder="Например: 35"
                                                        className={cn(inputClass, errors.roomsArea ? 'border-destructive' : '')}
                                                    />
                                                    <FieldError field="roomsArea" />
                                                </div>
                                            </>
                                        )}
                                        {showBathrooms(form.propertyType) && (
                                            <BathroomTypeRow
                                                label={
                                                    <>
                                                        <Bath className="w-3.5 h-3.5" /> Тип санузла
                                                    </>
                                                }
                                                value={bathroomTypeFromForm(form.bathrooms, form.amenities)}
                                                onChange={(t) =>
                                                    setForm((prev) => ({
                                                        ...prev,
                                                        ...applyBathroomTypeSelection(prev.amenities, t),
                                                    }))
                                                }
                                                error={errors.bathrooms}
                                            />
                                        )}
                                        {requiresAreaInSquareMeters(form.propertyType)
                                            && !(
                                                showLivingArea(form.propertyType)
                                                && showKitchenArea(form.propertyType)
                                            ) && (
                                            <div>
                                                <label className={labelClass}>
                                                    <Maximize className="w-3.5 h-3.5 inline mr-1 align-text-bottom" />
                                                    Площадь общая, м² *
                                                </label>
                                                <input
                                                    type="number"
                                                    min={AREA_MIN}
                                                    max={AREA_MAX}
                                                    value={form.area}
                                                    onChange={(e) => update('area', e.target.value)}
                                                    placeholder="Например: 65"
                                                    className={cn(inputClass, errors.area ? 'border-destructive' : '')}
                                                />
                                                <FieldError field="area" />
                                            </div>
                                        )}
                                        {needsLotArea(form.propertyType) && (
                                            <div>
                                                <label className={labelClass}>
                                                    <MapPin className="w-3.5 h-3.5 inline mr-1 align-text-bottom" />
                                                    Площадь участка, соток *
                                                </label>
                                                <input
                                                    type="number"
                                                    min={0.01}
                                                    step={0.01}
                                                    value={form.landArea}
                                                    onChange={(e) => update('landArea', e.target.value)}
                                                    placeholder="Например: 10"
                                                    className={cn(inputClass, errors.landArea ? 'border-destructive' : '')}
                                                />
                                                <FieldError field="landArea" />
                                            </div>
                                        )}
                                        {showFloor(form.propertyType) && showTotalFloors(form.propertyType) && (
                                            <FloorTotalFloorsRow
                                                floor={form.floor}
                                                totalFloors={form.totalFloors}
                                                onFloorChange={(v) => update('floor', v)}
                                                onTotalFloorsChange={(v) => update('totalFloors', v)}
                                                floorError={errors.floor}
                                                totalFloorsError={errors.totalFloors}
                                            />
                                        )}
                                        {showFloor(form.propertyType) && !showTotalFloors(form.propertyType) && (
                                            <div>
                                                <label className={labelClass}>Этаж</label>
                                                <input
                                                    type="number"
                                                    min={FLOOR_MIN}
                                                    max={FLOOR_MAX}
                                                    value={form.floor}
                                                    onChange={(e) => update('floor', e.target.value)}
                                                    placeholder="Например: 5 или подвал"
                                                    className={cn(inputClass, errors.floor ? 'border-destructive' : '')}
                                                />
                                                <FieldError field="floor" />
                                            </div>
                                        )}
                                        {!showFloor(form.propertyType) && showTotalFloors(form.propertyType) && (
                                            <NumericPillRow
                                                label="Этажей в доме"
                                                value={form.totalFloors}
                                                onChange={(v) => update('totalFloors', v)}
                                                min={TOTAL_FLOORS_MIN}
                                                max={TOTAL_FLOORS_MAX}
                                                error={errors.totalFloors}
                                            />
                                        )}
                                        {showYearBuilt(form.propertyType) && (
                                            <div>
                                                <label className={labelClass}>
                                                    <Calendar className="w-3.5 h-3.5 inline mr-1 align-text-bottom" />
                                                    Год постройки
                                                </label>
                                                <input
                                                    type="number"
                                                    min={YEAR_BUILT_MIN}
                                                    max={YEAR_BUILT_MAX}
                                                    value={form.yearBuilt}
                                                    onChange={(e) => update('yearBuilt', e.target.value)}
                                                    placeholder="Например: 2020"
                                                    className={cn(inputClass, errors.yearBuilt ? 'border-destructive' : '')}
                                                />
                                                <FieldError field="yearBuilt" />
                                            </div>
                                        )}
                                    </div>

                                    {requiresAreaInSquareMeters(form.propertyType)
                                        && showLivingArea(form.propertyType)
                                        && showKitchenArea(form.propertyType) && (
                                        <SegmentedAreaTripleRow
                                            area={form.area}
                                            livingArea={form.livingArea}
                                            kitchenArea={form.kitchenArea}
                                            onAreaChange={(v) => update('area', v)}
                                            onLivingAreaChange={(v) => update('livingArea', v)}
                                            onKitchenAreaChange={(v) => update('kitchenArea', v)}
                                            areaError={errors.area}
                                            livingError={errors.livingArea}
                                            kitchenError={errors.kitchenArea}
                                        />
                                    )}

                                    <div className="flex flex-col gap-4">
                                        {!requiresAreaInSquareMeters(form.propertyType)
                                            ? null
                                            : !showLivingArea(form.propertyType)
                                              ? null
                                              : showKitchenArea(form.propertyType)
                                                ? null
                                                : (
                                                    <div>
                                                        <label className={labelClass}>Площадь жилая, м²</label>
                                                        <input
                                                            type="number"
                                                            min={1}
                                                            value={form.livingArea}
                                                            onChange={(e) => update('livingArea', e.target.value)}
                                                            placeholder="Например: 40"
                                                            className={cn(
                                                                inputClass,
                                                                errors.livingArea ? 'border-destructive' : '',
                                                            )}
                                                        />
                                                        <FieldError field="livingArea" />
                                                    </div>
                                                  )}
                                        {!requiresAreaInSquareMeters(form.propertyType)
                                            ? null
                                            : showLivingArea(form.propertyType)
                                              ? null
                                              : showKitchenArea(form.propertyType) && (
                                                    <div>
                                                        <label className={labelClass}>Площадь кухни, м²</label>
                                                        <input
                                                            type="number"
                                                            min={1}
                                                            value={form.kitchenArea}
                                                            onChange={(e) => update('kitchenArea', e.target.value)}
                                                            placeholder="Например: 12"
                                                            className={cn(
                                                                inputClass,
                                                                errors.kitchenArea ? 'border-destructive' : '',
                                                            )}
                                                        />
                                                        <FieldError field="kitchenArea" />
                                                    </div>
                                                )}
                                    </div>
                                </div>

                                {(showRenovation(form.propertyType) || showBalcony(form.propertyType) || showDealConditions(form.dealType)) && (
                                    <div className="bg-card rounded-2xl shadow-card p-6 space-y-5">
                                        {showRenovation(form.propertyType) && (
                                            <div>
                                                <span className={labelClass}>Ремонт</span>
                                                <div className="flex flex-wrap gap-2">
                                                    {renovationOptionsForDeal(form.dealType).map((option) => (
                                                        <button
                                                            key={option}
                                                            type="button"
                                                            onClick={() => update('renovation', option)}
                                                            className={form.renovation === option ? chipActive : chipInactive}
                                                        >
                                                            {form.renovation === option && (
                                                                <Check className="h-3.5 w-3.5 inline mr-1.5 -mt-0.5" />
                                                            )}
                                                            {option}
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        {showBalcony(form.propertyType) && (
                                            <div>
                                                <span className={labelClass}>Балкон / Лоджия</span>
                                                <div className="flex flex-wrap gap-2">
                                                    {balconyOptions.map((option) => (
                                                        <button
                                                            key={option}
                                                            type="button"
                                                            onClick={() => update('balcony', option)}
                                                            className={form.balcony === option ? chipActive : chipInactive}
                                                        >
                                                            {form.balcony === option && (
                                                                <Check className="h-3.5 w-3.5 inline mr-1.5 -mt-0.5" />
                                                            )}
                                                            {option}
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        {showDealConditions(form.dealType) && (
                                            <div>
                                                <span className={labelClass}>Условия сделки</span>
                                                <div className="flex flex-wrap gap-2">
                                                    {dealConditionOptions(form.dealType, form.propertyType).map((option) => {
                                                        const selected = form.dealConditions.includes(option);
                                                        return (
                                                            <button
                                                                key={option}
                                                                type="button"
                                                                onClick={() => toggleDealCondition(option)}
                                                                className={selected ? chipActive : chipInactive}
                                                            >
                                                                {selected && (
                                                                    <Check className="h-3.5 w-3.5 inline mr-1.5 -mt-0.5" />
                                                                )}
                                                                {option}
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </>
                        )}

                        {/* Step 3: Удобства (карточки по макету) */}
                        {step === 3 && (
                            <div className="space-y-5">
                                {LISTING_AMENITY_GROUPS.map((group) => (
                                    <div
                                        key={group.id}
                                        className="bg-card rounded-2xl shadow-card p-6 space-y-4"
                                    >
                                        <h2 className="font-display text-base font-semibold text-foreground">
                                            {group.title}
                                        </h2>
                                        <div className="flex flex-wrap gap-2">
                                            {group.items.map((item) => {
                                                const selected = form.amenities.includes(item.id);
                                                return (
                                                    <button
                                                        key={item.id}
                                                        type="button"
                                                        onClick={() => toggleAmenity(item.id)}
                                                        className={cn(
                                                            'inline-flex items-center gap-1.5',
                                                            selected ? amenityChipActive : amenityChipInactive,
                                                        )}
                                                    >
                                                        {selected ? (
                                                            <Check className="h-3.5 w-3.5 shrink-0" strokeWidth={2.5} />
                                                        ) : null}
                                                        {item.label}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Step 4: Photos */}
                        {step === 4 && (
                            <div className="bg-card rounded-2xl shadow-card p-6 space-y-5">
                                <div>
                                    <h2 className="font-display text-lg font-semibold text-foreground">Фотографии</h2>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        Добавьте до {MAX_PHOTOS} фото. Первое фото станет обложкой.
                                    </p>
                                </div>

                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    onChange={handleFileInput}
                                    className="hidden"
                                />

                                <div
                                    className={cn(
                                        'grid grid-cols-2 sm:grid-cols-3 gap-3',
                                        dragOver && 'ring-2 ring-primary ring-offset-2 rounded-xl',
                                    )}
                                    onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
                                    onDragLeave={() => setDragOver(false)}
                                    onDrop={handleDrop}
                                >
                                    {form.photos.map((photo, i) => (
                                        <div key={i} className="relative aspect-[4/3] rounded-xl overflow-hidden group">
                                            {/* eslint-disable-next-line @next/next/no-img-element */}
                                            <img
                                                src={photo.url}
                                                alt={`Фото ${i + 1}`}
                                                className="w-full h-full object-cover"
                                            />
                                            {photo.uploading && (
                                                <div className="absolute inset-0 bg-foreground/30 flex items-center justify-center">
                                                    <Loader2 className="w-6 h-6 text-white animate-spin" />
                                                </div>
                                            )}
                                            {i === 0 && !photo.uploading && (
                                                <span className="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-primary text-primary-foreground text-xs font-semibold">
                                                    Обложка
                                                </span>
                                            )}
                                            <button
                                                type="button"
                                                onClick={() => removePhoto(i)}
                                                className="absolute top-2 right-2 p-1.5 rounded-full bg-card/80 backdrop-blur-sm text-destructive opacity-0 group-hover:opacity-100 transition-opacity"
                                            >
                                                <X className="h-4 w-4" />
                                            </button>
                                        </div>
                                    ))}

                                    {form.photos.length < MAX_PHOTOS && (
                                        <button
                                            type="button"
                                            onClick={() => fileInputRef.current?.click()}
                                            className={cn(
                                                'aspect-[4/3] rounded-xl border-2 border-dashed flex flex-col items-center justify-center gap-2 transition-colors group',
                                                dragOver
                                                    ? 'border-primary bg-primary/5 text-primary'
                                                    : 'border-border hover:border-primary/40 bg-surface text-muted-foreground hover:text-primary',
                                            )}
                                        >
                                            <div className="w-10 h-10 rounded-xl bg-muted flex items-center justify-center group-hover:bg-primary/10 transition-colors">
                                                <Upload className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors" />
                                            </div>
                                            <span className="text-xs font-medium text-muted-foreground">
                                                {dragOver ? 'Отпустите файлы' : 'Загрузить фото'}
                                            </span>
                                        </button>
                                    )}
                                </div>

                                <div className="bg-muted/50 rounded-xl p-4 flex items-start gap-3">
                                    <ImageIcon className="h-5 w-5 text-muted-foreground shrink-0 mt-0.5" />
                                    <div className="text-sm text-muted-foreground">
                                        <p className="font-medium text-foreground mb-1">Советы по фото:</p>
                                        <ul className="space-y-1 list-disc list-inside">
                                            <li>Используйте горизонтальные фото высокого качества</li>
                                            <li>Сфотографируйте все комнаты, кухню и ванную</li>
                                            <li>Обеспечьте хорошее освещение</li>
                                            <li>Допустимые форматы: JPEG, PNG, WebP (до 10 МБ)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Step 5: Location */}
                        {step === 5 && (
                            <>
                                <div className="bg-card rounded-2xl shadow-card p-6 space-y-5">
                                    <h2 className="font-display text-lg font-semibold text-foreground">Расположение</h2>

                                    <div ref={cityContainerRef} className="relative">
                                        <label className={labelClass}>Город *</label>
                                        <div className="relative">
                                            <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                                            <input
                                                value={cityQuery}
                                                onChange={(e) => {
                                                    setCityQuery(e.target.value);
                                                    setCityDropdownOpen(true);
                                                    if (!e.target.value.trim()) {
                                                        setForm((prev) => ({
                                                            ...prev,
                                                            cityId: null,
                                                            citySlug: '',
                                                            cityName: '',
                                                            streetName: '',
                                                            streetId: null,
                                                        }));
                                                        setStreetQuery('');
                                                    }
                                                }}
                                                onFocus={() => {
                                                    if (cityQuery.length >= 2) setCityDropdownOpen(true);
                                                }}
                                                placeholder="Начните вводить название города..."
                                                className={cn(inputClass, 'pl-10', errors.citySlug ? 'border-destructive' : '')}
                                            />
                                            {citySearching && (
                                                <Loader2 className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 animate-spin text-muted-foreground" />
                                            )}
                                        </div>
                                        <FieldError field="citySlug" />

                                        {cityDropdownOpen && cityResults.length > 0 && (
                                            <div className="absolute z-50 w-full mt-1 bg-popover border border-border rounded-xl shadow-lg max-h-60 overflow-auto">
                                                {cityResults.map((city) => {
                                                    const parts = formatCityLabel(city);
                                                    return (
                                                        <button
                                                            key={city.id}
                                                            type="button"
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
                                        <label className={labelClass}>Улица</label>
                                        <div className="relative">
                                            <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                                            <input
                                                value={streetQuery}
                                                onChange={(e) => {
                                                    setStreetQuery(e.target.value);
                                                    setStreetDropdownOpen(true);
                                                    setForm((prev) => ({
                                                        ...prev,
                                                        streetName: e.target.value,
                                                        streetId: null,
                                                    }));
                                                }}
                                                onFocus={() => {
                                                    if (form.cityId && streetQuery.length >= 1) setStreetDropdownOpen(true);
                                                }}
                                                placeholder={form.cityId ? 'Начните вводить название улицы...' : 'Сначала выберите город'}
                                                disabled={!form.cityId}
                                                className={cn(inputClass, 'pl-10', !form.cityId && 'opacity-60 cursor-not-allowed')}
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
                                                        type="button"
                                                        onClick={() => {
                                                            const displayName = street.type
                                                                ? `${street.type} ${street.name}`
                                                                : street.name;
                                                            setStreetQuery(displayName);
                                                            setForm((prev) => ({
                                                                ...prev,
                                                                streetName: displayName,
                                                                streetId: street.id,
                                                            }));
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
                                            <label className={labelClass}>Номер дома</label>
                                            <input
                                                value={form.building}
                                                onChange={(e) => update('building', e.target.value)}
                                                placeholder="Например: 58 (необязательно)"
                                                className={cn(inputClass, errors.building ? 'border-destructive' : '')}
                                            />
                                            <FieldError field="building" />
                                        </div>
                                        <div>
                                            <label className={labelClass}>Корпус</label>
                                            <input
                                                value={form.block}
                                                onChange={(e) => update('block', e.target.value)}
                                                placeholder="Например: 2 (необязательно)"
                                                className={inputClass}
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
                                                className="gap-2 w-full h-[46px] rounded-xl"
                                            >
                                                {geocoding ? <Loader2 className="w-4 h-4 animate-spin" /> : <MapPin className="w-4 h-4" />}
                                                Найти на карте
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <AddressMapPicker
                                    center={mapCenter}
                                    latitude={form.latitude}
                                    longitude={form.longitude}
                                    onCoordsChange={(lat, lng) =>
                                        setForm((prev) => ({ ...prev, latitude: lat, longitude: lng }))
                                    }
                                />
                            </>
                        )}

                        {/* Step 6: Условия размещения (посуточная аренда + цена) и контакты */}
                        {step === 6 && (
                            <>
                                {form.dealType === 'daily' && (
                                    <div className="bg-card rounded-2xl shadow-card p-6 space-y-5">
                                        <h2 className="font-display text-lg font-semibold text-foreground">Посуточная аренда</h2>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                            <div className="sm:col-span-2">
                                                <label className={labelClass}>
                                                    <Users className="w-3.5 h-3.5 inline mr-1 align-text-bottom" />
                                                    Максимум гостей *
                                                </label>
                                                <input
                                                    type="number"
                                                    inputMode="numeric"
                                                    min={1}
                                                    max={99}
                                                    step={1}
                                                    value={form.maxDailyGuests}
                                                    onChange={(e) => update('maxDailyGuests', e.target.value)}
                                                    placeholder="Например: 4"
                                                    className={cn(inputClass, errors.maxDailyGuests ? 'border-destructive' : '')}
                                                />
                                                <FieldError field="maxDailyGuests" />
                                            </div>
                                            <NumericPillRow
                                                label={
                                                    <>
                                                        <BedDouble className="w-3.5 h-3.5 inline mr-1 align-text-bottom" />
                                                        Односпальных кроватей *
                                                    </>
                                                }
                                                value={form.dailySingleBeds}
                                                onChange={(v) => update('dailySingleBeds', v)}
                                                min={0}
                                                max={DAILY_BEDS_MAX}
                                                plusDiscrete
                                                plusDiscretePlus="five"
                                                error={errors.dailySingleBeds}
                                            />
                                            <NumericPillRow
                                                label={
                                                    <>
                                                        <BedDouble className="w-3.5 h-3.5 inline mr-1 align-text-bottom" />
                                                        Двуспальных кроватей *
                                                    </>
                                                }
                                                value={form.dailyDoubleBeds}
                                                onChange={(v) => update('dailyDoubleBeds', v)}
                                                min={0}
                                                max={DAILY_BEDS_MAX}
                                                plusDiscrete
                                                plusDiscretePlus="five"
                                                error={errors.dailyDoubleBeds}
                                            />
                                            <div>
                                                <label className={labelClass}>Время заезда</label>
                                                <input
                                                    type="time"
                                                    value={form.checkInTime}
                                                    onChange={(e) => update('checkInTime', e.target.value)}
                                                    className={cn(inputClass, errors.checkInTime ? 'border-destructive' : '')}
                                                />
                                                <FieldError field="checkInTime" />
                                            </div>
                                            <div>
                                                <label className={labelClass}>Время выезда</label>
                                                <input
                                                    type="time"
                                                    value={form.checkOutTime}
                                                    onChange={(e) => update('checkOutTime', e.target.value)}
                                                    className={cn(inputClass, errors.checkOutTime ? 'border-destructive' : '')}
                                                />
                                                <FieldError field="checkOutTime" />
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <div className="bg-card rounded-2xl shadow-card p-6 space-y-5">
                                    <h2 className="font-display text-lg font-semibold text-foreground">Стоимость</h2>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div className="sm:col-span-2">
                                            <label className={labelClass}>Цена за сутки *</label>
                                            <input
                                                type="number"
                                                min={0}
                                                value={form.price}
                                                onChange={(e) => update('price', e.target.value)}
                                                placeholder="Например: 85"
                                                className={cn(inputClass, errors.price ? 'border-destructive' : '')}
                                            />
                                            <p className="text-xs text-muted-foreground mt-1">Стоимость аренды за одни сутки</p>
                                            <FieldError field="price" />
                                        </div>
                                        <div>
                                            <label className={labelClass}>Валюта</label>
                                            <Select value={form.currency} onValueChange={(v) => update('currency', v)}>
                                                <SelectTrigger
                                                    className={cn(
                                                        'h-[46px] w-full rounded-xl border-border bg-surface px-4',
                                                        'focus:ring-2 focus:ring-primary/20 focus:border-primary',
                                                    )}
                                                >
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
                                </div>

                                <div className="bg-card rounded-2xl shadow-card p-6 space-y-3">
                                    <h2 className="font-display text-lg font-semibold text-foreground">Контакты для связи</h2>
                                    <p className="text-sm text-muted-foreground leading-relaxed">
                                        Имя и телефон в объявлении берутся из вашего{' '}
                                        <Link href="/kabinet/profil" className="text-primary font-medium hover:underline">
                                            профиля
                                        </Link>{' '}
                                        и{' '}
                                        <Link href="/kabinet/telefony" className="text-primary font-medium hover:underline">
                                            подтверждённых номеров
                                        </Link>
                                        . Здесь указывать их не нужно.
                                    </p>
                                </div>
                            </>
                        )}
                    </motion.div>
                </AnimatePresence>

                {/* Navigation */}
                <div className="flex items-center justify-between pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={prev}
                        disabled={step === 1 || submitting}
                    >
                        Назад
                    </Button>

                    {step < TOTAL_STEPS ? (
                        <Button type="button" onClick={next} disabled={!canProceed()}>
                            Далее
                        </Button>
                    ) : (
                        <Button
                            type="button"
                            onClick={handleSubmit}
                            disabled={!canProceed() || submitting}
                            className="gap-2"
                        >
                            {submitting ? (
                                <>
                                    <Loader2 className="w-4 h-4 animate-spin" />
                                    Отправка…
                                </>
                            ) : (
                                <>
                                    <Check className="w-4 h-4" />
                                    Опубликовать
                                </>
                            )}
                        </Button>
                    )}
                </div>

                <PhoneAuthModal
                    open={smsAuthOpen}
                    onOpenChange={setSmsAuthOpen}
                    initialPhone=""
                    onSuccess={() => {
                        void submitProperty();
                    }}
                />
            </div>
    );
}
