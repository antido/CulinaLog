/**
 * The title block at the top of a page, with optional intro text
 * and action buttons on the right.
 */
export default function PageHeader({ title, intro, actions }) {
    return (
        <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div className="min-w-0">
                <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                    {title}
                </h1>
                {intro && <p className="mt-2 max-w-2xl text-plum">{intro}</p>}
            </div>
            {actions && (
                <div className="flex shrink-0 flex-wrap gap-3">{actions}</div>
            )}
        </div>
    );
}
