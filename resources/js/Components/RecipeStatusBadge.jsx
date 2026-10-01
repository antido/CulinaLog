import Badge from '@/Components/Badge';
import { capitalize } from '@/utils';

const tones = {
    draft: 'neutral',
    published: 'success',
    archived: 'warning',
};

/**
 * Shows a recipe's status. A recipe hidden by a moderator shows
 * "Hidden by a moderator" as well, because that overrides its status.
 */
export default function RecipeStatusBadge({ recipe }) {
    return (
        <span className="inline-flex flex-wrap gap-2">
            <Badge tone={tones[recipe.status]}>{capitalize(recipe.status)}</Badge>
            {recipe.is_hidden && <Badge tone="danger">Hidden by a moderator</Badge>}
        </span>
    );
}
