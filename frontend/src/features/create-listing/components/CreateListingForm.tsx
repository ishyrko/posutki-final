'use client';

import { useState, useRef, useCallback, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { motion, AnimatePresence } from 'framer-motion';
import { toast } from 'sonner';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    Upload,
    X,
    Home,
    MapPin,
    DollarSign,
    Image,
    FileText,
    BedDouble,
    Bath,
    Maximize,
    Building2,
    Calendar,
    Phone,
    User,
    Info,
    Loader2,
    Search,
    Warehouse,
    Store,
    Briefcase,
    TreePine,
    Car,
    Key,
    Tag,
} from 'lucide-react';
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
import { useSearchCities, useSearchStreets, useCreateProperty } from '../hooks';
import { uploadFile, FileTooLargeError } from '../api';
import type { ListingFormData, UploadedPhoto, CreatePropertyPayload, CitySearchResult } from '../types';
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
    TITLE_MAX_LENGTH,
    TITLE_MIN_LENGTH,
    TOTAL_FLOORS_MAX,
    TOTAL_FLOORS_MIN,
    YEAR_BUILT_MAX,
    YEAR_BUILT_MIN,
    getDescriptionFieldError,
    getTitleFieldError,
    isE164Phone,
    isFloorNotAboveTotalFloors,
    normalizePhoneForApi,
} from '../validation';
import { usePhones } from '@/features/phones/hooks';
import { PhoneVerifyDialog } from '@/features/phones/components/PhoneVerifyDialog';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { isAuthenticated } from '@/lib/auth';
import { PhoneAuthModal } from '@/features/sms-auth/components/PhoneAuthModal';
import { useUser } from '@/features/auth/hooks';

const STEPS = [
    { id: 1, label: 'Тип', icon: Home },
    { id: 2, label: 'Детали', icon: FileText },
    { id: 3, label: 'Фото', icon: Image },
    { id: 4, label: 'Адрес', icon: MapPin },
    { id: 5, label: 'Цена', icon: DollarSign },
];

const propertyTypes = [
    { value: 'apartment', label: 'Квартира', icon: Building2 },
    { value: 'house', label: 'Дом / Коттедж', icon: Home },
    { value: 'room', label: 'Комната', icon: BedDouble },
    { value: 'land', label: 'Участок', icon: MapPin },
    { value: 'garage', label: 'Гараж', icon: Car },
    { value: 'parking', label: 'Машиноместо', icon: Car },
    { value: 'dacha', label: 'Дача', icon: TreePine },
    { value: 'office', label: 'Офис', icon: Briefcase },
    { value: 'retail', label: 'Торговое помещение', icon: Store },
    { value: 'warehouse', label: 'Склад', icon: Warehouse },
];

const dealTypes = [
    { value: 'sale', label: 'Продажа', icon: Tag },
    { value: 'rent', label: 'Аренда', icon: Key },
    { value: 'daily', label: 'Посуточно', icon: Calendar },
];

const propertyCategories = [
    { value: 'residential', label: 'Жилая', icon: Home },
    { value: 'biz', label: 'Коммерческая', icon: Building2 },
    { value: 'auto', label: 'Для авто', icon: Car },
];

const propertyKindsByCategory: Record<string, string[]> = {
    residential: ['apartment', 'room', 'house', 'dacha', 'land'],
    biz: ['office', 'retail', 'warehouse'],
    auto: ['garage', 'parking'],
};

const dailyKinds = ['apartment', 'house', 'dacha'];
const lotAreaTypes = ['land', 'house', 'dacha'];

const requiresAreaInSquareMeters = (propertyType: string): boolean => propertyType !== 'land';
const needsLotArea = (propertyType: string): boolean => lotAreaTypes.includes(propertyType);

const availableCategories = (dealType: string): string[] =>
    dealType === 'daily'
        ? ['residential']
        : ['residential', 'biz', 'auto'];

const availablePropertyTypes = (dealType: string, category: string): string[] => {
    if (!category) return [];
    const categoryTypes = propertyKindsByCategory[category] ?? [];
    let types =
        dealType !== 'daily' ? categoryTypes : categoryTypes.filter((type) => dailyKinds.includes(type));
    if (dealType === 'rent') {
        types = types.filter((t) => t !== 'land');
    }
    return types;
};

const titlePlaceholderByType: Record<string, string> = {
    apartment: 'Например: Светлая 2-комнатная квартира у метро',
    room: 'Например: Комната 18 м² в 3-комнатной квартире',
    house: 'Например: Дом 140 м² с участком 8 соток',
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
    dealType: '',
    propertyCategory: '',
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
    dailyBedCount: '',
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
    contactName: '',
    contactPhone: '',
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
    const { data: phones = [], refetch: refetchPhones } = usePhones();
    const { data: user } = useUser();
    const contactNamePrefilledRef = useRef(false);
    const contactPhonePrefilledRef = useRef(false);
    const [verifyDialogOpen, setVerifyDialogOpen] = useState(false);
    const [smsAuthOpen, setSmsAuthOpen] = useState(false);

    useEffect(() => {
        if (!isAuthenticated() || contactNamePrefilledRef.current || !user) return;
        setForm((prev) => {
            if (contactNamePrefilledRef.current || prev.contactName.trim()) return prev;
            const name = [user.firstName, user.lastName].filter(Boolean).join(' ').trim();
            if (!name) return prev;
            contactNamePrefilledRef.current = true;
            return { ...prev, contactName: name };
        });
    }, [user]);

    useEffect(() => {
        if (!isAuthenticated() || contactPhonePrefilledRef.current) return;
        setForm((prev) => {
            if (contactPhonePrefilledRef.current || prev.contactPhone.trim()) return prev;
            const verifiedFromList = phones.find((p) => p.isVerified);
            const phone =
                verifiedFromList?.phone ??
                (user?.phone?.trim() && user.isPhoneVerified ? user.phone.trim() : '');
            if (!phone) return prev;
            contactPhonePrefilledRef.current = true;
            return { ...prev, contactPhone: phone };
        });
    }, [phones, user]);

    // Close dropdowns on outside click
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
            if (key === 'dealType') {
                const nextDeal = value as string;
                const renovationInvalidForDaily =
                    nextDeal === 'daily' && prev.renovation === 'Без ремонта';
                return {
                    ...prev,
                    dealType: nextDeal,
                    propertyCategory: '',
                    propertyType: '',
                    dealConditions: [],
                    maxDailyGuests: '',
                    dailyBedCount: '',
                    checkInTime: '',
                    checkOutTime: '',
                    roomsInDeal: '',
                    roomsArea: '',
                    ...(renovationInvalidForDaily ? { renovation: '' } : {}),
                };
            }
            if (key === 'propertyCategory') {
                return {
                    ...prev,
                    propertyCategory: value as string,
                    propertyType: '',
                    roomsInDeal: '',
                    roomsArea: '',
                };
            }
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
            if (key === 'dealType') {
                delete next.propertyCategory;
                delete next.propertyType;
                delete next.dealConditions;
                delete next.maxDailyGuests;
                delete next.dailyBedCount;
                delete next.checkInTime;
                delete next.checkOutTime;
                delete next.roomsInDeal;
                delete next.roomsArea;
            }
            if (key === 'propertyCategory') {
                delete next.propertyType;
                delete next.roomsInDeal;
                delete next.roomsArea;
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
                if (!form.dealType) errs.dealType = 'Выберите тип сделки';
                if (!form.propertyCategory) errs.propertyCategory = 'Выберите тип недвижимости';
                if (!form.propertyType) {
                    errs.propertyType = 'Выберите вид недвижимости';
                } else if (!availablePropertyTypes(form.dealType, form.propertyCategory).includes(form.propertyType)) {
                    errs.propertyType = 'Выберите вид недвижимости из списка';
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
                if (form.dealType === 'daily') {
                    if (!form.maxDailyGuests) {
                        errs.maxDailyGuests = 'Укажите максимум гостей';
                    } else if (!Number.isFinite(Number(form.maxDailyGuests)) || Number(form.maxDailyGuests) <= 0) {
                        errs.maxDailyGuests = 'Укажите корректное число гостей';
                    }
                    if (!form.dailyBedCount) {
                        errs.dailyBedCount = 'Укажите количество кроватей';
                    } else if (!Number.isFinite(Number(form.dailyBedCount)) || Number(form.dailyBedCount) <= 0) {
                        errs.dailyBedCount = 'Укажите корректное количество кроватей';
                    }
                    if (form.checkInTime && !/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(form.checkInTime)) {
                        errs.checkInTime = 'Формат времени: ЧЧ:ММ';
                    }
                    if (form.checkOutTime && !/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(form.checkOutTime)) {
                        errs.checkOutTime = 'Формат времени: ЧЧ:ММ';
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
                if (!form.cityId) errs.citySlug = 'Выберите город';
                break;
            case 5:
                if (!form.price) errs.price = 'Укажите цену';
                else if (Number(form.price) <= 0) errs.price = 'Цена должна быть положительной';
                if (!form.contactName.trim()) errs.contactName = 'Укажите ваше имя';
                if (!form.contactPhone.trim()) errs.contactPhone = 'Укажите телефон';
                else if (!isE164Phone(form.contactPhone)) {
                    errs.contactPhone = 'Введите корректный телефон';
                }
                break;
        }

        setErrors(errs);
        return Object.keys(errs).length === 0;
    }, [form]);

    const canProceed = useCallback((): boolean => {
        switch (step) {
            case 1:
                return !!(
                    form.dealType
                    && form.propertyCategory
                    && form.propertyType
                    && availablePropertyTypes(form.dealType, form.propertyCategory).includes(form.propertyType)
                );
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
                    && (form.dealType !== 'daily' || (
                        !!form.maxDailyGuests
                        && Number(form.maxDailyGuests) > 0
                        && !!form.dailyBedCount
                        && Number(form.dailyBedCount) > 0
                    ))
                );
            case 3: return true;
            case 4: return !!form.cityId;
            case 5:
                return !!(
                    form.price
                    && form.contactName.trim()
                    && form.contactPhone.trim()
                    && isE164Phone(form.contactPhone)
                );
            default: return false;
        }
    }, [step, form]);

    const next = () => { if (validateStep(step)) setStep((s) => Math.min(s + 1, 5)); };
    const prev = () => setStep((s) => Math.max(s - 1, 1));
    const goToStep = (target: number) => { if (target < step) setStep(target); };

    // --- File upload ---

    const handleFiles = async (files: FileList | File[]) => {
        const arr = Array.from(files);
        const remaining = MAX_PHOTOS - form.photos.length;
        if (arr.length > remaining) {
            toast.error(`Можно добавить ещё ${remaining} фото`);
        }
        const batch = arr.slice(0, remaining);

        const validFiles = batch.filter((f) => {
            if (!ACCEPTED_IMAGE_TYPES.includes(f.type)) {
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
        if (!validateStep(2) || !validateStep(5)) return;

        if (!isAuthenticated()) {
            setSmsAuthOpen(true);
            return;
        }

        const contactPhone = normalizePhoneForApi(form.contactPhone);
        let knownPhones = phones;
        try {
            const { data: freshPhones } = await refetchPhones();
            knownPhones = freshPhones ?? phones;
        } catch {
            knownPhones = phones;
        }

        const isPhoneVerified = knownPhones.some(
            (p) => normalizePhoneForApi(p.phone) === contactPhone && p.isVerified,
        );

        if (!isPhoneVerified) {
            setVerifyDialogOpen(true);
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
            dailyBedCount: form.dealType === 'daily' && form.dailyBedCount
                ? Number(form.dailyBedCount)
                : undefined,
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
            amenities: [],
            contactPhone: normalizePhoneForApi(form.contactPhone) || undefined,
            contactName: form.contactName.trim() || undefined,
        };

        try {
            await createProperty(payload);
            toast.success('Объявление отправлено на модерацию');
            router.push('/kabinet');
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

    const FieldError = ({ field }: { field: keyof ListingFormData }) =>
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

    return (
        <div className="pt-24 pb-16">
            <div className="container mx-auto px-4 max-w-3xl">
                <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} className="mb-8">
                    <h1 className="text-3xl font-bold font-display text-foreground mb-2">
                        Подать объявление
                    </h1>
                    <p className="text-muted-foreground">
                        Заполните информацию о вашем объекте недвижимости
                    </p>
                </motion.div>

                {/* Stepper */}
                <div className="flex items-center gap-1 mb-10 overflow-x-auto pb-2">
                    {STEPS.map((s, i) => {
                        const Icon = s.icon;
                        const isActive = step === s.id;
                        const isDone = step > s.id;
                        return (
                            <div key={s.id} className="flex items-center">
                                <button
                                    onClick={() => goToStep(s.id)}
                                    className={`flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all whitespace-nowrap ${isActive
                                            ? 'bg-primary text-primary-foreground shadow-primary'
                                            : isDone
                                                ? 'bg-accent text-accent-foreground cursor-pointer'
                                                : 'bg-muted text-muted-foreground cursor-default'
                                        }`}
                                >
                                    {isDone ? <Check className="w-4 h-4" /> : <Icon className="w-4 h-4" />}
                                    <span className="hidden sm:inline">{s.label}</span>
                                </button>
                                {i < STEPS.length - 1 && (
                                    <div className={`w-6 h-px mx-1 ${isDone ? 'bg-primary/40' : 'bg-border'}`} />
                                )}
                            </div>
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
                        className="bg-card rounded-2xl shadow-card border border-border p-6 sm:p-8"
                    >
                        {/* Step 1: Deal type + category + kind */}
                        {step === 1 && (
                            <div className="space-y-8">
                                <div>
                                    <h2 className="text-lg font-semibold text-foreground mb-1">Тип сделки</h2>
                                    <p className="text-sm text-muted-foreground mb-4">
                                        Выберите, что вы хотите сделать с объектом
                                    </p>
                                    <div className="grid grid-cols-1 min-[600px]:grid-cols-3 gap-3">
                                        {dealTypes.map((dt) => {
                                            const Icon = dt.icon;
                                            return (
                                                <button
                                                    key={dt.value}
                                                    onClick={() => update('dealType', dt.value)}
                                                    className={`flex w-full items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium border-2 transition-all text-left ${form.dealType === dt.value
                                                            ? 'border-primary bg-accent text-foreground'
                                                            : 'border-border hover:border-primary/30 text-muted-foreground hover:text-foreground'
                                                        }`}
                                                >
                                                    <Icon className="w-5 h-5 flex-shrink-0" />
                                                    {dt.label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                    <FieldError field="dealType" />
                                </div>

                                <div>
                                    <h2 className="text-lg font-semibold text-foreground mb-1">Тип недвижимости</h2>
                                    <p className="text-sm text-muted-foreground mb-4">Укажите категорию объекта</p>
                                    <div className="grid grid-cols-1 min-[600px]:grid-cols-3 gap-3">
                                        {propertyCategories
                                            .filter((category) => availableCategories(form.dealType).includes(category.value))
                                            .map((category) => {
                                            const Icon = category.icon;
                                            return (
                                                <button
                                                    key={category.value}
                                                    onClick={() => update('propertyCategory', category.value)}
                                                    className={`flex w-full items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium border-2 transition-all text-left ${form.propertyCategory === category.value
                                                            ? 'border-primary bg-accent text-foreground'
                                                            : 'border-border hover:border-primary/30 text-muted-foreground hover:text-foreground'
                                                        }`}
                                                >
                                                    <Icon className="w-5 h-5 flex-shrink-0" />
                                                    {category.label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                    <FieldError field="propertyCategory" />
                                </div>

                                <div>
                                    <h2 className="text-lg font-semibold text-foreground mb-1">Вид недвижимости</h2>
                                    <p className="text-sm text-muted-foreground mb-4">Выберите конкретный вид объекта</p>
                                    <div className="grid grid-cols-1 min-[600px]:grid-cols-3 gap-3">
                                        {availablePropertyTypes(form.dealType, form.propertyCategory).map((typeValue) => {
                                            const propertyType = propertyTypes.find((pt) => pt.value === typeValue);
                                            if (!propertyType) return null;
                                            const Icon = propertyType.icon;
                                            return (
                                                <button
                                                    key={propertyType.value}
                                                    onClick={() => update('propertyType', propertyType.value)}
                                                    className={`flex w-full items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium border-2 transition-all text-left ${form.propertyType === propertyType.value
                                                            ? 'border-primary bg-accent text-foreground'
                                                            : 'border-border hover:border-primary/30 text-muted-foreground hover:text-foreground'
                                                        }`}
                                                >
                                                    <Icon className="w-5 h-5 flex-shrink-0" />
                                                    {propertyType.label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                    <FieldError field="propertyType" />
                                </div>
                            </div>
                        )}

                        {/* Step 2: Details */}
                        {step === 2 && (
                            <div className="space-y-6 [&_label]:min-h-5 [&_label]:flex [&_label]:items-center [&_label]:gap-1.5">
                                <h2 className="text-lg font-semibold text-foreground">Описание объекта</h2>

                                <div>
                                    <Label htmlFor="title" className="text-foreground">
                                        Заголовок объявления *{' '}
                                        <span className="font-normal text-muted-foreground/80">
                                            ({TITLE_MIN_LENGTH}–{TITLE_MAX_LENGTH} символов)
                                        </span>
                                    </Label>
                                    <Input
                                        id="title"
                                        value={form.title}
                                        onChange={(e) => update('title', e.target.value)}
                                        onBlur={handleTitleBlur}
                                        placeholder={getTitlePlaceholder(form.propertyType)}
                                        className={`mt-1.5 ${errors.title ? 'border-destructive' : ''}`}
                                        maxLength={TITLE_MAX_LENGTH}
                                        aria-invalid={errors.title ? true : undefined}
                                    />
                                    <div className="flex items-center justify-between mt-1">
                                        <FieldError field="title" />
                                        <span className="text-xs text-muted-foreground/70 ml-auto tabular-nums">
                                            {form.title.length}/{TITLE_MAX_LENGTH}
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <Label htmlFor="description" className="text-foreground">
                                        Подробное описание *{' '}
                                        <span className="font-normal text-muted-foreground/80">
                                            ({DESCRIPTION_MIN_LENGTH}–{DESCRIPTION_MAX_LENGTH} символов)
                                        </span>
                                    </Label>
                                    <Textarea
                                        id="description"
                                        value={form.description}
                                        onChange={(e) => update('description', e.target.value)}
                                        onBlur={handleDescriptionBlur}
                                        placeholder="Опишите преимущества вашего объекта, расположение, состояние ремонта, инфраструктуру рядом..."
                                        rows={4}
                                        className={`mt-1.5 resize-none ${errors.description ? 'border-destructive' : ''}`}
                                        maxLength={DESCRIPTION_MAX_LENGTH}
                                        aria-invalid={errors.description ? true : undefined}
                                    />
                                    <div className="flex items-center justify-between mt-1">
                                        <FieldError field="description" />
                                        <span className="text-xs text-muted-foreground/70 ml-auto tabular-nums">
                                            {form.description.length}/{DESCRIPTION_MAX_LENGTH}
                                        </span>
                                    </div>
                                </div>

                                <div className="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-4">
                                    {showRooms(form.propertyType) && (
                                        <div>
                                        <Label className="text-foreground flex items-center gap-1.5">
                                            <BedDouble className="w-3.5 h-3.5" /> Комнат {roomsRequired(form.propertyType) ? '*' : ''}
                                        </Label>
                                        <Input
                                            type="number" min={ROOMS_MIN} max={ROOMS_MAX}
                                            value={form.rooms}
                                            onChange={(e) => update('rooms', e.target.value)}
                                            placeholder="Например: 3"
                                            className={`mt-1.5 ${errors.rooms ? 'border-destructive' : ''}`}
                                        />
                                        <FieldError field="rooms" />
                                        </div>
                                    )}
                                    {showRoomDealFields(form.propertyType, form.dealType) && (
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
                                                    placeholder="Например: 2"
                                                    className={`mt-1.5 ${errors.roomsInDeal ? 'border-destructive' : ''}`}
                                                />
                                                <FieldError field="roomsInDeal" />
                                            </div>
                                            <div>
                                                <Label className="text-foreground flex items-center gap-1.5 min-h-5">
                                                    <Maximize className="w-3.5 h-3.5" /> Площадь комнат в сделке, м² *
                                                </Label>
                                                <Input
                                                    type="number"
                                                    min={AREA_MIN}
                                                    max={AREA_MAX}
                                                    step={0.1}
                                                    value={form.roomsArea}
                                                    onChange={(e) => update('roomsArea', e.target.value)}
                                                    placeholder="Например: 35"
                                                    className={`mt-1.5 ${errors.roomsArea ? 'border-destructive' : ''}`}
                                                />
                                                <FieldError field="roomsArea" />
                                            </div>
                                        </>
                                    )}
                                    {showBathrooms(form.propertyType) && (
                                        <div>
                                        <Label className="text-foreground flex items-center gap-1.5">
                                            <Bath className="w-3.5 h-3.5" /> Санузлов
                                        </Label>
                                        <Input
                                            type="number" min={BATHROOMS_MIN} max={BATHROOMS_MAX}
                                            value={form.bathrooms}
                                            onChange={(e) => update('bathrooms', e.target.value)}
                                            placeholder="Например: 1"
                                            className={`mt-1.5 ${errors.bathrooms ? 'border-destructive' : ''}`}
                                        />
                                        <FieldError field="bathrooms" />
                                        </div>
                                    )}
                                    {requiresAreaInSquareMeters(form.propertyType) && (
                                        <div>
                                            <Label className="text-foreground flex items-center gap-1.5 min-h-5">
                                                <Maximize className="w-3.5 h-3.5" /> Площадь общая, м² *
                                            </Label>
                                            <Input
                                                type="number" min={AREA_MIN} max={AREA_MAX}
                                                value={form.area}
                                                onChange={(e) => update('area', e.target.value)}
                                                placeholder="Например: 65"
                                                className={`mt-1.5 ${errors.area ? 'border-destructive' : ''}`}
                                            />
                                            <FieldError field="area" />
                                        </div>
                                    )}
                                    {needsLotArea(form.propertyType) && (
                                        <div>
                                            <Label className="text-foreground flex items-center gap-1.5 min-h-5">
                                                <MapPin className="w-3.5 h-3.5" /> Площадь участка, соток *
                                            </Label>
                                            <Input
                                                type="number"
                                                min={0.01}
                                                step={0.01}
                                                value={form.landArea}
                                                onChange={(e) => update('landArea', e.target.value)}
                                                placeholder="Например: 10"
                                                className={`mt-1.5 ${errors.landArea ? 'border-destructive' : ''}`}
                                            />
                                            <FieldError field="landArea" />
                                        </div>
                                    )}
                                    {showFloor(form.propertyType) && (
                                        <div>
                                        <Label className="text-foreground flex items-center min-h-5">Этаж</Label>
                                        <Input
                                            type="number" min={FLOOR_MIN} max={FLOOR_MAX}
                                            value={form.floor}
                                            onChange={(e) => update('floor', e.target.value)}
                                            placeholder="Например: 5"
                                            className={`mt-1.5 ${errors.floor ? 'border-destructive' : ''}`}
                                        />
                                        <FieldError field="floor" />
                                        </div>
                                    )}
                                    {showTotalFloors(form.propertyType) && (
                                        <div>
                                        <Label className="text-foreground">Этажей в доме</Label>
                                        <Input
                                            type="number" min={TOTAL_FLOORS_MIN} max={TOTAL_FLOORS_MAX}
                                            value={form.totalFloors}
                                            onChange={(e) => update('totalFloors', e.target.value)}
                                            placeholder="Например: 9"
                                            className={`mt-1.5 ${errors.totalFloors ? 'border-destructive' : ''}`}
                                        />
                                        <FieldError field="totalFloors" />
                                        </div>
                                    )}
                                    {showYearBuilt(form.propertyType) && (
                                        <div>
                                        <Label className="text-foreground flex items-center gap-1.5">
                                            <Calendar className="w-3.5 h-3.5" /> Год постройки
                                        </Label>
                                        <Input
                                            type="number" min={YEAR_BUILT_MIN} max={YEAR_BUILT_MAX}
                                            value={form.yearBuilt}
                                            onChange={(e) => update('yearBuilt', e.target.value)}
                                            placeholder="Например: 2020"
                                            className={`mt-1.5 ${errors.yearBuilt ? 'border-destructive' : ''}`}
                                        />
                                        <FieldError field="yearBuilt" />
                                        </div>
                                    )}
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {showLivingArea(form.propertyType) && (
                                        <div>
                                            <Label className="text-foreground">Площадь жилая, м²</Label>
                                            <Input
                                                type="number" min={1}
                                                value={form.livingArea}
                                                onChange={(e) => update('livingArea', e.target.value)}
                                                placeholder="Например: 40"
                                                className={`mt-1.5 ${errors.livingArea ? 'border-destructive' : ''}`}
                                            />
                                            <FieldError field="livingArea" />
                                        </div>
                                    )}
                                    {showKitchenArea(form.propertyType) && (
                                        <div>
                                            <Label className="text-foreground">Площадь кухни, м²</Label>
                                            <Input
                                                type="number" min={1}
                                                value={form.kitchenArea}
                                                onChange={(e) => update('kitchenArea', e.target.value)}
                                                placeholder="Например: 12"
                                                className={`mt-1.5 ${errors.kitchenArea ? 'border-destructive' : ''}`}
                                            />
                                            <FieldError field="kitchenArea" />
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
                                                placeholder="Например: 4"
                                                className={`mt-1.5 ${errors.maxDailyGuests ? 'border-destructive' : ''}`}
                                            />
                                            <FieldError field="maxDailyGuests" />
                                        </div>
                                        <div>
                                            <Label className="text-foreground">Количество кроватей *</Label>
                                            <Input
                                                type="number"
                                                min={1}
                                                value={form.dailyBedCount}
                                                onChange={(e) => update('dailyBedCount', e.target.value)}
                                                placeholder="Например: 2"
                                                className={`mt-1.5 ${errors.dailyBedCount ? 'border-destructive' : ''}`}
                                            />
                                            <FieldError field="dailyBedCount" />
                                        </div>
                                        <div>
                                            <Label className="text-foreground">Время заезда</Label>
                                            <Input
                                                type="time"
                                                value={form.checkInTime}
                                                onChange={(e) => update('checkInTime', e.target.value)}
                                                className={`mt-1.5 ${errors.checkInTime ? 'border-destructive' : ''}`}
                                            />
                                            <FieldError field="checkInTime" />
                                        </div>
                                        <div>
                                            <Label className="text-foreground">Время выезда</Label>
                                            <Input
                                                type="time"
                                                value={form.checkOutTime}
                                                onChange={(e) => update('checkOutTime', e.target.value)}
                                                className={`mt-1.5 ${errors.checkOutTime ? 'border-destructive' : ''}`}
                                            />
                                            <FieldError field="checkOutTime" />
                                        </div>
                                    </div>
                                )}

                                {showRenovation(form.propertyType) && (
                                    <div>
                                        <Label className="text-foreground">Ремонт</Label>
                                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2">
                                            {renovationOptionsForDeal(form.dealType).map((option) => (
                                                <button
                                                    key={option}
                                                    onClick={() => update('renovation', option)}
                                                    className={`px-3 py-2 rounded-lg text-sm border transition-all ${form.renovation === option
                                                            ? 'border-primary bg-accent text-foreground'
                                                            : 'border-border text-muted-foreground hover:border-primary/30 hover:text-foreground'
                                                        }`}
                                                >
                                                    {option}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {showBalcony(form.propertyType) && (
                                    <div>
                                        <Label className="text-foreground">Балкон / Лоджия</Label>
                                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2">
                                            {balconyOptions.map((option) => (
                                                <button
                                                    key={option}
                                                    onClick={() => update('balcony', option)}
                                                    className={`px-3 py-2 rounded-lg text-sm border transition-all ${form.balcony === option
                                                            ? 'border-primary bg-accent text-foreground'
                                                            : 'border-border text-muted-foreground hover:border-primary/30 hover:text-foreground'
                                                        }`}
                                                >
                                                    {option}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {showDealConditions(form.dealType) && (
                                    <div>
                                        <Label className="text-foreground">Условия сделки</Label>
                                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2">
                                            {dealConditionOptions(form.dealType, form.propertyType).map((option) => {
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
                        )}

                        {/* Step 3: Photos */}
                        {step === 3 && (
                            <div className="space-y-6">
                                <div>
                                    <h2 className="text-lg font-semibold text-foreground mb-1">Фотографии</h2>
                                    <p className="text-sm text-muted-foreground">
                                        Добавьте до {MAX_PHOTOS} фото вашего объекта. Первое фото станет обложкой.
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
                                    className={`grid grid-cols-2 sm:grid-cols-3 gap-3 ${dragOver ? 'ring-2 ring-primary ring-offset-2 rounded-xl' : ''
                                        }`}
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

                                    {form.photos.length < MAX_PHOTOS && (
                                        <button
                                            onClick={() => fileInputRef.current?.click()}
                                            className={`aspect-[4/3] rounded-xl border-2 border-dashed flex flex-col items-center justify-center gap-2 transition-colors ${dragOver
                                                    ? 'border-primary bg-primary/5 text-primary'
                                                    : 'border-border hover:border-primary/40 text-muted-foreground hover:text-primary'
                                                }`}
                                        >
                                            <Upload className="w-6 h-6" />
                                            <span className="text-xs font-medium">
                                                {dragOver ? 'Отпустите файлы' : 'Добавить'}
                                            </span>
                                        </button>
                                    )}
                                </div>

                                <div className="flex items-start gap-2 p-3 rounded-xl bg-accent/50 text-sm text-accent-foreground">
                                    <Info className="w-4 h-4 mt-0.5 flex-shrink-0" />
                                    <span>
                                        Качественные фото увеличивают просмотры объявления в 3 раза. Рекомендуем
                                        горизонтальные снимки при хорошем освещении. Допустимые форматы: JPEG, PNG, WebP (до 10 МБ).
                                    </span>
                                </div>
                            </div>
                        )}

                        {/* Step 4: Location */}
                        {step === 4 && (
                            <div className="space-y-6">
                                <h2 className="text-lg font-semibold text-foreground">Расположение</h2>

                                {/* City autocomplete */}
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
                                            className={`pl-9 ${errors.citySlug ? 'border-destructive' : ''}`}
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

                                {/* Street autocomplete */}
                                <div ref={streetContainerRef} className="relative">
                                    <Label className="text-foreground">Улица</Label>
                                    <div className="relative mt-1.5">
                                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                                        <Input
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

                                {/* Building number */}
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <Label className="text-foreground">Номер дома</Label>
                                        <Input
                                            value={form.building}
                                            onChange={(e) => update('building', e.target.value)}
                                            placeholder="Например: 58 (необязательно)"
                                            className={`mt-1.5 ${errors.building ? 'border-destructive' : ''}`}
                                        />
                                        <FieldError field="building" />
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
                                            {geocoding ? <Loader2 className="w-4 h-4 animate-spin" /> : <MapPin className="w-4 h-4" />}
                                            Найти на карте
                                        </Button>
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
                            </div>
                        )}

                        {/* Step 5: Price & Contact */}
                        {step === 5 && (
                            <div className="space-y-8">
                                <div className="space-y-4">
                                    <h2 className="text-lg font-semibold text-foreground">Стоимость</h2>
                                    <div className="grid grid-cols-3 gap-3">
                                        <div className="col-span-2">
                                            <Label className="text-foreground">Цена *</Label>
                                            <Input
                                                type="number" min={0}
                                                value={form.price}
                                                onChange={(e) => update('price', e.target.value)}
                                                placeholder="Например: 150 000"
                                                className={`mt-1.5 ${errors.price ? 'border-destructive' : ''}`}
                                            />
                                            <FieldError field="price" />
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
                                </div>

                                <div className="space-y-4">
                                    <h2 className="text-lg font-semibold text-foreground">Контакты</h2>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <Label className="text-foreground flex items-center gap-1.5">
                                                <User className="w-3.5 h-3.5" /> Ваше имя *
                                            </Label>
                                            <Input
                                                value={form.contactName}
                                                onChange={(e) => update('contactName', e.target.value)}
                                                placeholder="Например: Иван Иванов"
                                                className={`mt-1.5 ${errors.contactName ? 'border-destructive' : ''}`}
                                                maxLength={100}
                                            />
                                            <FieldError field="contactName" />
                                        </div>
                                        <div>
                                            <Label className="text-foreground flex items-center gap-1.5">
                                                <Phone className="w-3.5 h-3.5" /> Телефон *
                                            </Label>
                                            <Input
                                                type="tel"
                                                value={form.contactPhone}
                                                onChange={(e) => update('contactPhone', e.target.value)}
                                                placeholder="Например: +375 (29) 123-45-67"
                                                className={`mt-1.5 ${errors.contactPhone ? 'border-destructive' : ''}`}
                                                maxLength={20}
                                            />
                                            <FieldError field="contactPhone" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </motion.div>
                </AnimatePresence>

                {/* Navigation */}
                <div className="flex items-center justify-between mt-6">
                    <Button
                        variant="ghost"
                        onClick={prev}
                        disabled={step === 1 || submitting}
                        className="gap-2 text-muted-foreground"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Назад
                    </Button>

                    {step < 5 ? (
                        <Button
                            onClick={next}
                            disabled={!canProceed()}
                            className="gap-2 bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0"
                        >
                            Далее
                            <ArrowRight className="w-4 h-4" />
                        </Button>
                    ) : (
                        <Button
                            onClick={handleSubmit}
                            disabled={!canProceed() || submitting}
                            className="gap-2 bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0"
                        >
                            {submitting ? (
                                <>
                                    <Loader2 className="w-4 h-4 animate-spin" />
                                    Отправка...
                                </>
                            ) : (
                                <>
                                    <Check className="w-4 h-4" />
                                    Отправить на модерацию
                                </>
                            )}
                        </Button>
                    )}
                </div>
            </div>

            <PhoneVerifyDialog
                open={verifyDialogOpen}
                onOpenChange={setVerifyDialogOpen}
                initialPhone={form.contactPhone.trim()}
                onVerified={() => {
                    setVerifyDialogOpen(false);
                    submitProperty();
                }}
            />
            <PhoneAuthModal
                open={smsAuthOpen}
                onOpenChange={setSmsAuthOpen}
                initialPhone={form.contactPhone.trim()}
                onSuccess={() => {
                    void submitProperty();
                }}
            />
        </div>
    );
}
