/** A white surface with a border. Used for forms, lists and panels. */
export default function Card({ as: Tag = 'div', className = '', children }) {
    return (
        <Tag
            className={`rounded-2xl border border-line bg-paper p-5 sm:p-6 ${className}`}
        >
            {children}
        </Tag>
    );
}
