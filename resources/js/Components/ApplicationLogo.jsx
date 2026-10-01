/**
 * The CulinaLog mark: a ladle resting across an open notebook page.
 */
export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <svg
            {...props}
            className={className}
            viewBox="0 0 32 32"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <rect width="32" height="32" rx="8" fill="#4a2a60" />
            <path
                d="M9 9.5h9M9 14h6M9 18.5h4"
                stroke="#f6f5f1"
                strokeWidth="2.2"
                strokeLinecap="round"
            />
            <circle cx="20.5" cy="20.5" r="4.5" fill="#f2b01e" />
        </svg>
    );
}
