import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

export function useToastListener() {
    const { toast: toastResponse } = usePage().flash;

    useEffect(() => {
        if (toastResponse) {
            switch (toastResponse.variant) {
                case 'affirmative':
                    toast.success(toastResponse.message);
                    break;
                case 'informative':
                    toast.info(toastResponse.message);
                    break;
                case 'preventive':
                    toast.warning(toastResponse.message);
                    break;
                case 'destructive':
                    toast.error(toastResponse.message);
                    break;
                default:
                    toast(toastResponse.message);
                    break;
            }
        }
    }, [toastResponse]);
}
