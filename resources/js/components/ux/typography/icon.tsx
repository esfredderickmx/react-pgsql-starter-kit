import type { LucideIcon } from 'lucide-react';
import type { AriaAttributes } from 'react';

type IconProps = {
    iconNode: LucideIcon;
    className?: string;
} & AriaAttributes;

function Icon({ iconNode: IconComponent, className, ...props }: IconProps) {
    if (!IconComponent) {
        return null;
    }

    return <IconComponent className={className} {...props} />;
}

export { Icon };
