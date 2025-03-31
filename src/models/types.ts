export type TUser = {
    id: number;
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
};

export type TEvent = {
    id: number;
    title: string;
    description: string;
    imageUrl: string;   
    date: string;
    startTime: string;
    endTime: string;
    location: string;
    capacity: number;
    price: number;
    currentAttendees: number;
    wineSelection: string;
    activities: string;
    isPrivate: boolean;
};

export type TWineCollection = {
    id?: number; // Optional for new entries
    eventId: number;
    name: string;
    variety: string;
    year: number;
    region: string;
    price: number;
    description: string;
    imageUrl: string;
};

export type TActivity = {
    id?: number; // Optional for new entries
    eventId: number;
    duration: number;
    difficulty: 'beginner' | 'intermediate' | 'advanced';
    materials: string[];
};

export type TBooking = {
    id: number;
    userId: number;
    eventId: number;
    eventTitle: string;
    date: string;
};

export type Error = {
    error: string;
};