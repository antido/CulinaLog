/**
 * Shown when a list has nothing in it yet. Say what belongs here and,
 * when possible, pass a button as `action` so the user can add the first one.
 */
export default function EmptyState({ title, message, action }) {
    return (
        <div className="rounded-2xl border border-dashed border-line bg-paper px-6 py-12 text-center">
            <h2 className="text-lg font-semibold">{title}</h2>
            {message && (
                <p className="mx-auto mt-1 max-w-md text-plum">{message}</p>
            )}
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
