import { inputClasses } from '@/Components/TextInput';

export default function Textarea({ className = '', rows = 4, ...props }) {
    return (
        <textarea
            {...props}
            rows={rows}
            className={`${inputClasses} ${className}`}
        />
    );
}
