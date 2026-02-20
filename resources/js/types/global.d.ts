import type { Auth } from '@/types/data/auth';
import type { Response } from '@/types/data/response';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
        flashDataType: {
            toast?: Response;
        };
    }
}
