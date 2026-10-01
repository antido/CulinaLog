export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={`size-6 rounded border-line text-aubergine shadow-sm ${className}`}
        />
    );
}
