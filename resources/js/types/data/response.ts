import { App } from '@/wayfinder/types';
import EmphasisVariant = App.Enums.Frontend.EmphasisVariant;

export type Response = {
    variant: EmphasisVariant;
    message: string;
};
