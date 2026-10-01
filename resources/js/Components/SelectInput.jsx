import { inputClasses } from '@/Components/TextInput';

export default function SelectInput({ className = '', children, ...props }) {
    return (
        <select {...props} className={`${inputClasses} ${className}`}>
            {children}
        </select>
    );
}
