export type ReviewStatus = 'pending' | 'approved' | 'rejected' | 'deleted';

export interface ReviewAuthor {
    id: number;
    firstName: string;
    lastName: string;
}

export interface PropertyReview {
    id: number;
    rating: number;
    text: string | null;
    author: ReviewAuthor;
    createdAt: string;
    ownerReply?: string | null;
    ownerRepliedAt?: string | null;
}

export interface PropertyReviewsResponse {
    items: PropertyReview[];
    ratingAvg: number | null;
    reviewCount: number;
}

export interface ViewerReview {
    id: number;
    status: ReviewStatus;
}

export interface OwnerReviewAuthor {
    firstName: string;
    lastName: string;
    phone?: string | null;
    email?: string | null;
}

export interface OwnerReviewItem {
    id: number;
    rating: number;
    text: string | null;
    shareDataWithOwner: boolean;
    createdAt: string;
    ownerReply?: string | null;
    ownerRepliedAt?: string | null;
    author: OwnerReviewAuthor;
    property: {
        id: number;
        title: string;
    };
}

export interface OwnerPropertyReviewsResponse {
    items: OwnerReviewItem[];
    property?: {
        id: number;
        title: string;
    };
}

export interface OwnerReviewsResponse {
    items: OwnerReviewItem[];
}

export interface MyReviewProperty {
    id: number;
    title: string;
    type: string;
}

export interface MyReviewItem {
    id: number;
    rating: number;
    text: string | null;
    status: Exclude<ReviewStatus, 'deleted'>;
    moderationComment?: string | null;
    createdAt: string;
    ownerReply?: string | null;
    ownerRepliedAt?: string | null;
    property: MyReviewProperty;
}

export interface MyReviewsResponse {
    items: MyReviewItem[];
}
