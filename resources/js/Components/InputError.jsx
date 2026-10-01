export default function InputError({ message, className = '', ...props }) {
    return message ? (
        <p
            {...props}
            role="alert"
            className={`text-sm font-medium text-chili ${className}`}
        >
            {message}
        </p>
    ) : null;
}
