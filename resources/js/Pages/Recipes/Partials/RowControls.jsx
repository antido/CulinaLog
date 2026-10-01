const buttonClasses =
    'inline-flex min-h-11 items-center rounded-lg border border-line bg-paper px-3 text-sm font-medium text-ink hover:border-aubergine hover:text-aubergine disabled:cursor-not-allowed disabled:opacity-40';

/**
 * The "Move up", "Move down" and "Remove" buttons for one row of a list
 * in the recipe form. `label` names the row for screen readers,
 * for example "ingredient 2".
 */
export default function RowControls({
    label,
    isFirst,
    isLast,
    canRemove,
    onMoveUp,
    onMoveDown,
    onRemove,
}) {
    return (
        <div className="flex flex-wrap gap-2">
            <button
                type="button"
                className={buttonClasses}
                onClick={onMoveUp}
                disabled={isFirst}
                aria-label={`Move ${label} up`}
            >
                Up
            </button>
            <button
                type="button"
                className={buttonClasses}
                onClick={onMoveDown}
                disabled={isLast}
                aria-label={`Move ${label} down`}
            >
                Down
            </button>
            <button
                type="button"
                className={`${buttonClasses} hover:border-chili hover:text-chili`}
                onClick={onRemove}
                disabled={!canRemove}
                aria-label={`Remove ${label}`}
            >
                Remove
            </button>
        </div>
    );
}
