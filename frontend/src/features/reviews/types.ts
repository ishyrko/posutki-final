export type ReviewStatus = 'pending' | 'approved' | 'rejected';

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
