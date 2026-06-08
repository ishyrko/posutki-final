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
    X,
    Info,
    Plus,
    Check,
    Link as LinkIcon,
} from 'lucide-react';
import Link from 'next/link';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { BynCurrencyMark } from '@/components/BynCurrency';
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
import { geocodeAddress as yandexGeocode } from '@/lib/yandex-geocoder';
import { useProperty, useUpdateProperty } from '@/features/properties/hooks';
import type { UpdatePropertyPayload } from '@/features/properties/api';
import { isPropertyEditable } from '@/features/properties/types';
import type { Property as PropertyItem, PropertyStatus } from '@/features/properties/types';
import { useCityAutocompleteResults, useSearchStreets } from '@/features/create-listing/hooks';
import { LISTING_AMENITY_GROUPS } from '@/features/create-listing/listing-amenity-groups';
import { PAYMENT_METHOD_OPTIONS } from '@/features/properties/payment-methods';
import { PropertyPhotoGrid } from '@/features/create-listing/components/PropertyPhotoGrid';
import { createUploadedPhotoFromUrl } from '@/features/create-listing/photo-utils';
import type { UploadedPhoto } from '@/features/create-listing/types';
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
    AREA_MAX,
    AREA_MIN,
    BATHROOMS_MAX,
    BATHROOMS_MIN,
    DESCRIPTION_MAX_LENGTH,
    DESCRIPTION_MIN_LENGTH,
    FLOOR_MAX,
    FLOOR_MIN,
    MAX_PHOTOS,
    MIN_PHOTOS,
    ROOMS_MAX,
    ROOMS_MIN,
    DAILY_BEDS_MAX,
    MAX_DAILY_GUESTS,
    TITLE_MAX_LENGTH,
    TITLE_MIN_LENGTH,
    TOTAL_FLOORS_MAX,
    TOTAL_FLOORS_MIN,
    YEAR_BUILT_MAX,
    YEAR_BUILT_MIN,
    cityFieldLabel,
    cityFieldNameGenitive,
    cityFieldNameInText,
    cityNotFoundMessage,
    getDescriptionFieldError,
    getTitleFieldError,
    getCityFieldError,
    getApartmentStreetFieldError,
    getApartmentBuildingFieldError,
    getAddressAfterCityQueryChange,
    isCitySelectionPending,
    isNumberInRange,
} from '@/features/create-listing/validation';
import type { CitySearchResult, AdditionalService } from '@/features/create-listing/types';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { getApiErrorMessage } from '@/lib/api-error';
import {
    applyBathroomTypeSelection,
    bathroomTypeFromForm,
    BathroomTypeRow,
    FloorTotalFloorsRow,
    NumericPillRow,
    resolvedBathroomsForPayload,
    SegmentedAreaTripleRow,
} from '@/features/create-listing/components/listing-parameter-controls';

const propertyTypes = [
    { value: 'apartment', label: 'Квартира' },
    { value: 'house', label: 'Дом / коттедж' },
];
const lotAreaTypes = ['house'];

const requiresAreaInSquareMeters = (_propertyType: string): boolean => true;
const needsLotArea = (propertyType: string): boolean => lotAreaTypes.includes(propertyType);

const DEFAULT_CENTER: [number, number] = [53.9045, 27.5615];

const DEAL_RULE_OPTIONS: { id: string; label: string }[] = [
    { id: 'contactless_checkin', label: 'Бесконтактное заселение' },
    { id: '24h_checkin', label: 'Круглосуточное заселение' },
    { id: 'pets_allowed', label: 'Можно с животными' },
    { id: 'parties_allowed', label: 'Сдаётся для вечеринок' },
    { id: 'accounting_docs', label: 'Отчётные документы' },
    { id: 'no_smoking', label: 'Курение запрещено' },
    { id: 'children_allowed', label: 'Можно с детьми' },
];

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
    dailySingleBeds: string;
    dailyDoubleBeds: string;
    checkInTime: string;
    checkOutTime: string;
    floor: string;
    totalFloors: string;
    yearBuilt: string;
    renovation: string;
    balcony: string;
    dealConditions: string[];
    paymentMethods: string[];
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
    images: UploadedPhoto[];
    amenities: string[];
    weekendPriceNegotiable: boolean;
    additionalServices: AdditionalService[];
    instagramUrl: string;
    websiteUrl: string;
    externalCalendarUrls: string[];
}

type EditTitleDescriptionErrors = Partial<Pick<EditFormData, 'title' | 'description'>>;

function mapPropertyToForm(property: PropertyItem): EditFormData {
    const revisionData = property.pendingRevisionStatus ? property.pendingRevisionData : null;
    const rawType = revisionData?.type ?? property.type;
    const type = rawType === 'house' ? 'house' : 'apartment';
    const rawDealConditions = revisionData?.dealConditions ?? property.specifications.dealConditions ?? [];

    return {
        type,
        dealType: 'daily',
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
        dailySingleBeds:
            revisionData?.dailySingleBeds != null
                ? String(revisionData.dailySingleBeds)
                : property.specifications.dailySingleBeds != null
                  ? String(property.specifications.dailySingleBeds)
                  : '0',
        dailyDoubleBeds:
            revisionData?.dailyDoubleBeds != null
                ? String(revisionData.dailyDoubleBeds)
                : property.specifications.dailyDoubleBeds != null
                  ? String(property.specifications.dailyDoubleBeds)
                  : '0',
        checkInTime: revisionData?.checkInTime ?? property.specifications.checkInTime ?? '',
        checkOutTime: revisionData?.checkOutTime ?? property.specifications.checkOutTime ?? '',
        floor: String(revisionData?.floor ?? property.specifications.floor ?? ''),
        totalFloors: String(revisionData?.totalFloors ?? property.specifications.totalFloors ?? ''),
        yearBuilt: String(revisionData?.yearBuilt ?? property.specifications.yearBuilt ?? ''),
        renovation: revisionData?.renovation ?? property.specifications.renovation ?? '',
        balcony: revisionData?.balcony ?? property.specifications.balcony ?? '',
        dealConditions: sanitizeDealConditionsForPropertyType(type, rawDealConditions),
        paymentMethods: [
            ...(revisionData?.paymentMethods ?? property.specifications.paymentMethods ?? []),
        ],
        price: String(revisionData?.priceAmount ?? property.price.amount),
        currency: 'BYN',
        cityId: revisionData?.cityId ?? property.address.cityId,
        cityName: property.address.cityName || '',
        streetName: (revisionData?.streetName ?? property.address.streetName) || '',
        streetId: revisionData?.streetId ?? property.address.streetId ?? null,
        building: revisionData?.building ?? property.address.building ?? '',
        block: revisionData?.block ?? property.address.block ?? '',
        latitude: revisionData?.latitude ?? property.coordinates?.latitude ?? null,
        longitude: revisionData?.longitude ?? property.coordinates?.longitude ?? null,
        images: revisionData?.images
            ? revisionData.images.map((url) => createUploadedPhotoFromUrl(url))
            : property.images.map((img) => createUploadedPhotoFromUrl(img.url)),
        amenities: [...(revisionData?.amenities ?? property.amenities ?? [])],
        weekendPriceNegotiable: property.weekendPriceNegotiable ?? false,
        additionalServices: (
            (revisionData?.additionalServices ?? property.additionalServices) as Array<{ name: string; price: number }> | undefined
        )?.map((s) => ({ name: s.name, price: String(s.price) })) ?? [{ name: '', price: '' }],
        instagramUrl: (revisionData?.instagramUrl ?? property.instagramUrl) as string ?? '',
        websiteUrl: (revisionData?.websiteUrl ?? property.websiteUrl) as string ?? '',
        externalCalendarUrls: (
            (revisionData?.externalCalendarUrls ?? property.externalCalendarUrls) as string[] | undefined
        )?.length
            ? [...((revisionData?.externalCalendarUrls ?? property.externalCalendarUrls) as string[])]
            : [''],
    };
}

function editBlockedBackHref(status?: PropertyStatus): string {
    return status === 'archived'
        ? '/kabinet/moi-obyavleniya/neaktivnye/'
        : '/kabinet/moi-obyavleniya/aktivnye/';
}

function editBlockedHint(status?: PropertyStatus): string {
    if (status === 'archived') {
        return 'Сначала активируйте объявление в разделе «Неактивные», затем его можно будет отредактировать.';
    }
    if (status === 'deleted') {
        return 'Удалённые объявления восстановить нельзя.';
    }
    return 'Объявление не найдено или недоступно для редактирования. Если оно скрыто, активируйте его в разделе «Неактивные».';
}

function EditPropertyUnavailable({
    backHref,
    hint,
}: {
    backHref: string;
    hint: string;
}) {
    return (
        <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3 }}>
            <div className="flex items-center gap-3 mb-6">
                <Button variant="ghost" size="icon" asChild>
                    <Link href={backHref}>
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                </Button>
                <div>
                    <h1 className="text-2xl font-display font-bold text-foreground">Редактирование объявления</h1>
                </div>
            </div>
            <div className="max-w-3xl rounded-2xl border border-border bg-card p-6 shadow-card space-y-4">
                <p className="text-foreground font-medium">
                    Нельзя изменять удалённое или неактивное объявление.
                </p>
                <p className="text-sm text-muted-foreground">{hint}</p>
                <Button asChild variant="outline">
                    <Link href={backHref}>Вернуться к моим объявлениям</Link>
                </Button>
            </div>
        </motion.div>
    );
}

export default function EditPropertyPage() {
    const { id } = useParams<{ id: string }>();
    const propertyId = Number(id);
    const router = useRouter();
    const { data: property, isLoading, isError } = useProperty(propertyId);
    const { mutateAsync: updateProperty, isPending: saving } = useUpdateProperty();

    const [form, setForm] = useState<EditFormData | null>(null);
    const [fieldErrors, setFieldErrors] = useState<EditTitleDescriptionErrors>({});
    const [geocoding, setGeocoding] = useState(false);

    const [cityQuery, setCityQuery] = useState('');
    const [cityInputUnlocked, setCityInputUnlocked] = useState(false);
    const [cityDropdownOpen, setCityDropdownOpen] = useState(false);
    const debouncedCityQuery = useDebouncedValue(cityQuery, 300);
    const { cityResults, citySearching, showCityNotFound } = useCityAutocompleteResults(debouncedCityQuery);
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
        const initialForm = mapPropertyToForm(property);
        setForm(initialForm);
        setCityQuery(property.address.cityName || '');
        setStreetQuery(initialForm.streetName);
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
            if (key === 'type') {
                const nextType = value as string;
                return {
                    ...prev,
                    type: nextType,
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
            const dealConditions = exists
                ? prev.dealConditions.filter((c) => c !== condition)
                : [...prev.dealConditions, condition];
            return { ...prev, dealConditions };
        });
    }, []);

    const togglePaymentMethod = useCallback((method: string) => {
        setForm((prev) => {
            if (!prev) return prev;
            const exists = prev.paymentMethods.includes(method);
            const paymentMethods = exists
                ? prev.paymentMethods.filter((m) => m !== method)
                : [...prev.paymentMethods, method];
            return { ...prev, paymentMethods };
        });
    }, []);

    const toggleAmenity = useCallback((id: string) => {
        setForm((prev) => {
            if (!prev) return prev;
            const has = prev.amenities.includes(id);
            return {
                ...prev,
                amenities: has ? prev.amenities.filter((a) => a !== id) : [...prev.amenities, id],
            };
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
            const coords = await yandexGeocode(query);
            if (coords) {
                setForm((prev) => (prev ? { ...prev, latitude: coords.latitude, longitude: coords.longitude } : prev));
            } else {
                toast.error('Не удалось определить координаты по адресу');
            }
        } catch {
            toast.error('Ошибка геокодирования');
        } finally {
            setGeocoding(false);
        }
    };

    const handleSubmit = async () => {
        if (!form || !Number.isFinite(propertyId) || propertyId <= 0) return;
        if (property && !isPropertyEditable(property.status)) {
            toast.error('Нельзя изменять удалённое или неактивное объявление');
            return;
        }
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
        const dailySingleBeds = Number(form.dailySingleBeds);
        const dailyDoubleBeds = Number(form.dailyDoubleBeds);
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
            && bathroomTypeFromForm(form.bathrooms, form.amenities) === null
        ) {
            toast.error('Выберите тип санузла');
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
            if (maxDailyGuests > MAX_DAILY_GUESTS) {
                toast.error(`Максимум ${MAX_DAILY_GUESTS} гостей`);
                return;
            }
            if (
                !Number.isFinite(dailySingleBeds)
                || dailySingleBeds < 0
                || dailySingleBeds > DAILY_BEDS_MAX
            ) {
                toast.error(`Односпальных кроватей: от 0 до ${DAILY_BEDS_MAX}`);
                return;
            }
            if (
                !Number.isFinite(dailyDoubleBeds)
                || dailyDoubleBeds < 0
                || dailyDoubleBeds > DAILY_BEDS_MAX
            ) {
                toast.error(`Двуспальных кроватей: от 0 до ${DAILY_BEDS_MAX}`);
                return;
            }
            if (dailySingleBeds + dailyDoubleBeds < 1) {
                toast.error('Укажите хотя бы одну кровать');
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
        const readyImageCount = form.images.filter((img) => !img.uploading).length;
        if (readyImageCount < MIN_PHOTOS) {
            toast.error(`Загрузите не менее ${MIN_PHOTOS} фотографий`);
            return;
        }

        const cityErr = getCityFieldError(form.cityId, form.type);
        if (cityErr) {
            toast.error(cityErr);
            return;
        }

        const streetErr = getApartmentStreetFieldError(form.type, form.streetName, form.streetId);
        if (streetErr) {
            toast.error(streetErr);
            return;
        }
        const buildingErr = getApartmentBuildingFieldError(form.type, form.building);
        if (buildingErr) {
            toast.error(buildingErr);
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
                bathrooms: showBathrooms(form.type)
                    ? resolvedBathroomsForPayload(form.bathrooms, form.amenities)
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
                dealConditions: form.dealConditions.length ? form.dealConditions : undefined,
                paymentMethods: form.paymentMethods.length ? form.paymentMethods : undefined,
                maxDailyGuests: form.dealType === 'daily' && form.maxDailyGuests ? Number(form.maxDailyGuests) : undefined,
                dailySingleBeds: form.dealType === 'daily' ? dailySingleBeds : undefined,
                dailyDoubleBeds: form.dealType === 'daily' ? dailyDoubleBeds : undefined,
                checkInTime: form.dealType === 'daily' && form.checkInTime ? form.checkInTime : undefined,
                checkOutTime: form.dealType === 'daily' && form.checkOutTime ? form.checkOutTime : undefined,
                building: form.building.trim(),
                block: form.block.trim() || undefined,
                cityId: form.cityId ?? undefined,
                streetId: form.streetId,
                streetName: form.streetId ? undefined : form.streetName.trim() || undefined,
                coordinates:
                    form.latitude !== null && form.longitude !== null
                        ? { latitude: form.latitude, longitude: form.longitude }
                        : undefined,
                images: form.images.filter((img) => !img.uploading).map((img) => img.url),
                amenities: form.amenities,
                weekendPriceNegotiable: form.weekendPriceNegotiable,
                additionalServices: form.type === 'house'
                    ? form.additionalServices
                        .filter((s) => s.name.trim() !== '' && s.price !== '')
                        .map((s) => ({ name: s.name.trim(), price: Number(s.price) }))
                    : undefined,
                instagramUrl: form.type === 'house' && form.instagramUrl.trim()
                    ? form.instagramUrl.trim()
                    : undefined,
                websiteUrl: form.type === 'house' && form.websiteUrl.trim()
                    ? form.websiteUrl.trim()
                    : undefined,
                externalCalendarUrls: form.dealType === 'daily'
                    ? form.externalCalendarUrls.map((url) => url.trim()).filter(Boolean)
                    : undefined,
            };
            const result = await updateProperty({ id: propertyId, data: payload });
            toast.success(result.message);
            router.push('/kabinet/moi-obyavleniya/aktivnye');
        } catch (err: unknown) {
            toast.error(getApiErrorMessage(err, 'Не удалось сохранить изменения'));
        }
    };

    const formatCityLabel = (city: CitySearchResult) => {
        const parts: string[] = [city.name];
        if (city.districtName) parts.push(city.districtName + ' р-н');
        if (city.regionName) parts.push(city.regionName + ' обл.');
        if (city.ruralCouncil) parts.push(city.ruralCouncil + ' с/с');
        return parts;
    };

    if (isLoading) {
        return (
            <div className="flex items-center justify-center py-24">
                <Loader2 className="w-8 h-8 animate-spin text-primary" />
            </div>
        );
    }

    if (isError || !property) {
        return (
            <EditPropertyUnavailable
                backHref={editBlockedBackHref()}
                hint={editBlockedHint()}
            />
        );
    }

    if (!isPropertyEditable(property.status)) {
        return (
            <EditPropertyUnavailable
                backHref={editBlockedBackHref(property.status)}
                hint={editBlockedHint(property.status)}
            />
        );
    }

    if (!form) {
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
    const availablePropertyTypes = propertyTypes;
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
                            <p className="mt-1.5 text-sm text-muted-foreground rounded-lg border border-border bg-muted/30 px-3 py-2">
                                Посуточная аренда
                            </p>
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
                        <div className="flex flex-col gap-4">
                            {showRooms(form.type) && (
                                <div>
                                    <NumericPillRow
                                        label={
                                            <>
                                                <BedDouble className="w-3.5 h-3.5" /> Количество комнат
                                                {roomsRequired(form.type) ? (
                                                    <span className="text-destructive">*</span>
                                                ) : null}
                                            </>
                                        }
                                        value={form.rooms}
                                        onChange={(v) => update('rooms', v)}
                                        min={ROOMS_MIN}
                                        max={ROOMS_MAX}
                                        plusDiscrete
                                    />
                                </div>
                            )}
                            {showRoomDealFields(form.type, form.dealType) && (
                                <>
                                    <div>
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
                                    <BathroomTypeRow
                                        label={
                                            <>
                                                <Bath className="w-3.5 h-3.5" /> Санузел
                                            </>
                                        }
                                        value={bathroomTypeFromForm(form.bathrooms, form.amenities)}
                                        onChange={(t) =>
                                            setForm((prev) =>
                                                prev ? { ...prev, ...applyBathroomTypeSelection(prev.amenities, t) } : prev,
                                            )
                                        }
                                    />
                                </div>
                            )}
                            {requiresAreaInSquareMeters(form.type)
                                && !(showLivingArea(form.type) && showKitchenArea(form.type)) && (
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
                            {showFloor(form.type) && showTotalFloors(form.type) && (
                                <div>
                                    <FloorTotalFloorsRow
                                        floor={form.floor}
                                        totalFloors={form.totalFloors}
                                        onFloorChange={(v) => update('floor', v)}
                                        onTotalFloorsChange={(v) => update('totalFloors', v)}
                                    />
                                </div>
                            )}
                            {showFloor(form.type) && !showTotalFloors(form.type) && (
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
                            {!showFloor(form.type) && showTotalFloors(form.type) && (
                                <div>
                                    <NumericPillRow
                                        label="Этажей в доме"
                                        value={form.totalFloors}
                                        onChange={(v) => update('totalFloors', v)}
                                        min={TOTAL_FLOORS_MIN}
                                        max={TOTAL_FLOORS_MAX}
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

                        {requiresAreaInSquareMeters(form.type)
                            && showLivingArea(form.type)
                            && showKitchenArea(form.type) && (
                            <div className="mt-4">
                                <SegmentedAreaTripleRow
                                    area={form.area}
                                    livingArea={form.livingArea}
                                    kitchenArea={form.kitchenArea}
                                    onAreaChange={(v) => update('area', v)}
                                    onLivingAreaChange={(v) => update('livingArea', v)}
                                    onKitchenAreaChange={(v) => update('kitchenArea', v)}
                                />
                            </div>
                        )}

                        <div className="flex flex-col gap-4">
                            {!requiresAreaInSquareMeters(form.type)
                                ? null
                                : !showLivingArea(form.type)
                                  ? null
                                  : showKitchenArea(form.type)
                                    ? null
                                    : (
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
                            {!requiresAreaInSquareMeters(form.type)
                                ? null
                                : showLivingArea(form.type)
                                  ? null
                                  : showKitchenArea(form.type) && (
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
                                <div className="sm:col-span-2">
                                    <Label className="text-foreground">Максимальное число гостей *</Label>
                                    <Input
                                        type="number"
                                        inputMode="numeric"
                                        min={1}
                                        max={MAX_DAILY_GUESTS}
                                        step={1}
                                        value={form.maxDailyGuests}
                                        onChange={(e) => update('maxDailyGuests', e.target.value)}
                                        className="mt-1.5"
                                    />
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
                                />
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

                {/* Amenities */}
                <div className="space-y-4">
                    {LISTING_AMENITY_GROUPS.map((group) => {
                        const visibleItems = group.items.filter(
                            (item) => !item.propertyTypes || item.propertyTypes.includes(form.type),
                        );
                        if (visibleItems.length === 0) return null;
                        return (
                            <section key={group.id} className="bg-card rounded-2xl shadow-card border border-border p-6">
                                <h2 className="text-base font-semibold text-foreground mb-4">{group.title}</h2>
                                <div className="flex flex-wrap gap-2">
                                    {visibleItems.map((item) => {
                                        const selected = form.amenities.includes(item.id);
                                        return (
                                            <button
                                                key={item.id}
                                                type="button"
                                                onClick={() => toggleAmenity(item.id)}
                                                className={cn(
                                                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-medium transition-all',
                                                    selected
                                                        ? 'bg-primary text-primary-foreground border border-primary'
                                                        : 'bg-muted/70 border border-transparent text-foreground hover:bg-muted',
                                                )}
                                            >
                                                {selected && <Check className="h-3.5 w-3.5 shrink-0" strokeWidth={2.5} />}
                                                {item.label}
                                            </button>
                                        );
                                    })}
                                </div>
                            </section>
                        );
                    })}
                </div>

                {/* Photos */}
                <section className="bg-card rounded-2xl shadow-card border border-border p-6">
                    <h2 className="text-lg font-semibold text-foreground mb-1">Фотографии</h2>
                    <p className="text-sm text-muted-foreground mb-4">
                        Не менее {MIN_PHOTOS} и не более {MAX_PHOTOS} фото. Перетаскивайте фото, чтобы изменить порядок (на телефоне — удерживайте и перетащите), первое станет обложкой.
                    </p>
                    <PropertyPhotoGrid
                        photos={form.images}
                        onChange={(images) => setForm((prev) => (
                            prev
                                ? {
                                    ...prev,
                                    images: typeof images === 'function' ? images(prev.images) : images,
                                }
                                : prev
                        ))}
                        addLabel="Добавить"
                    />
                </section>

                {/* Address */}
                <section className="bg-card rounded-2xl shadow-card border border-border p-6">
                    <h2 className="text-lg font-semibold text-foreground mb-4">Расположение</h2>
                    <div className="space-y-4">
                        <div ref={cityContainerRef} className="relative">
                            <Label className="text-foreground" htmlFor="edit-listing-city">
                                {cityFieldLabel(form.type)}
                            </Label>
                            <div className="relative mt-1.5">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                                <Input
                                    id="edit-listing-city"
                                    value={cityQuery}
                                    onChange={(e) => {
                                        const value = e.target.value;
                                        setCityQuery(value);
                                        setCityDropdownOpen(true);
                                        setForm((prev) => {
                                            if (!prev) return prev;
                                            const { next, clearStreet } = getAddressAfterCityQueryChange(prev, value);
                                            if (clearStreet) {
                                                setStreetQuery('');
                                            }
                                            return next;
                                        });
                                    }}
                                    onFocus={() => {
                                        setCityInputUnlocked(true);
                                        setCityDropdownOpen(true);
                                    }}
                                    onBlur={() => {
                                        window.setTimeout(() => setCityDropdownOpen(false), 200);
                                    }}
                                    placeholder="Например: Минск"
                                    type="search"
                                    name="posutki-edit-listing-city-search"
                                    autoComplete="one-time-code"
                                    autoCorrect="off"
                                    spellCheck={false}
                                    readOnly={!cityInputUnlocked}
                                    data-lpignore="true"
                                    data-1p-ignore
                                    aria-describedby="edit-listing-city-hint"
                                    className={cn(
                                        'pl-9',
                                        isCitySelectionPending(cityQuery, form.cityId)
                                            ? 'border-amber-500/80 focus-visible:ring-amber-500/30'
                                            : '',
                                    )}
                                />
                                {citySearching && (
                                    <Loader2 className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 animate-spin text-muted-foreground" />
                                )}

                                {cityDropdownOpen && cityResults.length > 0 && (
                                    <div className="absolute left-0 right-0 top-full z-50 mt-1 max-h-60 overflow-auto rounded-xl border border-border bg-popover shadow-lg">
                                        {cityResults.map((city) => {
                                            const parts = formatCityLabel(city);
                                            return (
                                                <button
                                                    key={city.id}
                                                    type="button"
                                                    onMouseDown={(e) => e.preventDefault()}
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
                                {cityDropdownOpen && showCityNotFound && (
                                    <div className="absolute left-0 right-0 top-full z-50 mt-1 rounded-xl border border-border bg-popover px-4 py-3 text-sm text-muted-foreground shadow-lg">
                                        {cityNotFoundMessage(form.type)}
                                    </div>
                                )}
                            </div>
                            {(form.cityId || !isCitySelectionPending(cityQuery, form.cityId)) && (
                                <p id="edit-listing-city-hint" className="text-xs text-muted-foreground mt-1.5">
                                    {form.cityId
                                        ? `Выбран: ${form.cityName}`
                                        : 'Выберите город из списка или введите название для поиска'}
                                </p>
                            )}
                        </div>

                        <div ref={streetContainerRef} className="relative">
                            <Label className="text-foreground" htmlFor="edit-listing-street">
                                Улица{form.type === 'apartment' ? ' *' : ''}
                            </Label>
                            {!form.cityId && (
                                <p className="text-xs text-muted-foreground mt-1 mb-1.5">
                                    {`Поле станет доступно после выбора ${cityFieldNameGenitive(form.type)} из списка подсказок выше`}
                                </p>
                            )}
                            <div className="relative mt-1.5">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                                <Input
                                    id="edit-listing-street"
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
                                    placeholder={
                                        form.cityId
                                            ? 'Начните вводить и выберите улицу из списка (можно ввести вручную)'
                                            : `Сначала выберите ${cityFieldNameInText(form.type)} из списка`
                                    }
                                    disabled={!form.cityId}
                                    aria-disabled={!form.cityId}
                                    className={cn('pl-9', !form.cityId && 'opacity-60 cursor-not-allowed bg-muted/40')}
                                />
                                {streetSearching && (
                                    <Loader2 className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 animate-spin text-muted-foreground" />
                                )}

                                {streetDropdownOpen && streetResults.length > 0 && (
                                    <div className="absolute left-0 right-0 top-full z-50 mt-1 max-h-60 overflow-auto rounded-xl border border-border bg-popover shadow-lg">
                                        {streetResults.map((street) => (
                                            <button
                                                key={street.id}
                                                type="button"
                                                onMouseDown={(e) => e.preventDefault()}
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
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <Label className="text-foreground">
                                    Номер дома{form.type === 'apartment' ? ' *' : ''}
                                </Label>
                                <Input
                                    value={form.building}
                                    onChange={(e) => update('building', e.target.value)}
                                    placeholder={
                                        form.type === 'apartment'
                                            ? 'Например: 58'
                                            : 'Например: 58 (необязательно)'
                                    }
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

                {/* Additional services + links (house only) */}
                {form.type === 'house' && (
                    <section className="bg-card rounded-2xl shadow-card border border-border p-6 space-y-5">
                        <div>
                            <h2 className="text-lg font-semibold text-foreground mb-1">Дополнительные услуги и цены</h2>
                            <p className="text-xs text-muted-foreground">Напр. &quot;Баня — 30р&quot;</p>
                        </div>
                        <div className="space-y-3">
                            {form.additionalServices.map((svc, idx) => (
                                <div key={idx} className="flex items-center gap-2">
                                    <Input
                                        value={svc.name}
                                        onChange={(e) => {
                                            const next = form.additionalServices.map((s, i) =>
                                                i === idx ? { ...s, name: e.target.value } : s
                                            );
                                            update('additionalServices', next);
                                        }}
                                        placeholder="Название услуги"
                                        className="flex-1"
                                    />
                                    <Input
                                        type="number"
                                        min={0}
                                        value={svc.price}
                                        onChange={(e) => {
                                            const next = form.additionalServices.map((s, i) =>
                                                i === idx ? { ...s, price: e.target.value } : s
                                            );
                                            update('additionalServices', next);
                                        }}
                                        placeholder="Цена"
                                        className="w-28"
                                    />
                                    <BynCurrencyMark className="shrink-0" />
                                    <button
                                        type="button"
                                        onClick={() => {
                                            const next = form.additionalServices.filter((_, i) => i !== idx);
                                            update('additionalServices', next.length > 0 ? next : [{ name: '', price: '' }]);
                                        }}
                                        className="text-destructive hover:text-destructive/80 transition-colors shrink-0"
                                        aria-label="Удалить услугу"
                                    >
                                        <X className="w-4 h-4" />
                                    </button>
                                </div>
                            ))}
                        </div>
                        <Button
                            type="button"
                            onClick={() => update('additionalServices', [...form.additionalServices, { name: '', price: '' }])}
                            className="gap-2"
                        >
                            <Plus className="w-4 h-4" />
                            Добавить
                        </Button>
                        <div className="pt-2 space-y-4">
                            <div>
                                <Label className="text-foreground flex items-center gap-1 mb-1.5">
                                    <LinkIcon className="w-3.5 h-3.5" />
                                    Ссылка на Instagram:
                                </Label>
                                <Input
                                    value={form.instagramUrl}
                                    onChange={(e) => update('instagramUrl', e.target.value)}
                                    placeholder="https://instagram.com/yourprofile"
                                />
                            </div>
                            <div>
                                <Label className="text-foreground flex items-center gap-1 mb-1.5">
                                    <LinkIcon className="w-3.5 h-3.5" />
                                    Ссылка на Сайт/Страницу:
                                </Label>
                                <Input
                                    value={form.websiteUrl}
                                    onChange={(e) => update('websiteUrl', e.target.value)}
                                    placeholder="https://example.com"
                                />
                            </div>
                        </div>
                    </section>
                )}

                {form.dealType === 'daily' && (
                    <section className="bg-card rounded-2xl shadow-card border border-border p-6 space-y-5">
                        <div>
                            <h2 className="text-lg font-semibold text-foreground mb-1">Синхронизация календарей</h2>
                            <p className="text-xs text-muted-foreground">
                                Добавьте ссылки на ICS-календари с Куфара, Суточно и других площадок
                            </p>
                        </div>
                        <div className="space-y-3">
                            {form.externalCalendarUrls.map((url, idx) => (
                                <div key={idx} className="flex items-center gap-2">
                                    <LinkIcon className="w-4 h-4 shrink-0 text-muted-foreground" />
                                    <Input
                                        value={url}
                                        onChange={(e) => {
                                            const next = form.externalCalendarUrls.map((item, i) =>
                                                i === idx ? e.target.value : item
                                            );
                                            update('externalCalendarUrls', next);
                                        }}
                                        placeholder="https://kufar.by/ical/..."
                                        className="flex-1"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => {
                                            const next = form.externalCalendarUrls.filter((_, i) => i !== idx);
                                            update('externalCalendarUrls', next.length > 0 ? next : ['']);
                                        }}
                                        className="text-destructive hover:text-destructive/80 transition-colors shrink-0"
                                        aria-label="Удалить календарь"
                                    >
                                        <X className="w-4 h-4" />
                                    </button>
                                </div>
                            ))}
                        </div>
                        <Button
                            type="button"
                            onClick={() => update('externalCalendarUrls', [...form.externalCalendarUrls, ''])}
                            className="gap-2"
                        >
                            <Plus className="w-4 h-4" />
                            Добавить календарь
                        </Button>
                    </section>
                )}

                {/* Deal rules (daily) */}
                {['apartment', 'house'].includes(form.type) && (
                    <section className="bg-card rounded-2xl shadow-card border border-border p-6">
                        <h2 className="text-lg font-semibold text-foreground mb-4">Правила и условия</h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                            {DEAL_RULE_OPTIONS.map(({ id, label }) => (
                                <div key={id} className="flex items-center gap-2.5">
                                    <Checkbox
                                        id={`rule_${id}`}
                                        checked={form.dealConditions.includes(id)}
                                        onCheckedChange={() => toggleDealCondition(id)}
                                    />
                                    <label
                                        htmlFor={`rule_${id}`}
                                        className="text-sm text-foreground cursor-pointer select-none"
                                    >
                                        {label}
                                    </label>
                                </div>
                            ))}
                        </div>
                    </section>
                )}

                <section className="bg-card rounded-2xl shadow-card border border-border p-6">
                    <h2 className="text-lg font-semibold text-foreground mb-4">Способы оплаты</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                        {PAYMENT_METHOD_OPTIONS.map(({ id, label }) => (
                            <div key={id} className="flex items-center gap-2.5">
                                <Checkbox
                                    id={`payment_${id}`}
                                    checked={form.paymentMethods.includes(id)}
                                    onCheckedChange={() => togglePaymentMethod(id)}
                                />
                                <label
                                    htmlFor={`payment_${id}`}
                                    className="text-sm text-foreground cursor-pointer select-none"
                                >
                                    {label}
                                </label>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Price */}
                <section className="bg-card rounded-2xl shadow-card border border-border p-6">
                    <h2 className="text-lg font-semibold text-foreground mb-4">Стоимость</h2>
                    <div>
                        <Label className="text-foreground">Цена *</Label>
                        <div className="flex items-center gap-2 mt-1.5">
                            <Input
                                type="number"
                                min={0}
                                value={form.price}
                                onChange={(e) => update('price', e.target.value)}
                                className="flex-1"
                            />
                            <BynCurrencyMark className="shrink-0" />
                        </div>
                    </div>
                    <div className="flex items-center gap-2 mt-4">
                        <Checkbox
                            id="weekendPriceNegotiable"
                            checked={form.weekendPriceNegotiable}
                            onCheckedChange={(v) => update('weekendPriceNegotiable', !!v)}
                        />
                        <label htmlFor="weekendPriceNegotiable" className="text-sm text-foreground cursor-pointer select-none">
                            В выходные и праздничные дни цена договорная
                        </label>
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
