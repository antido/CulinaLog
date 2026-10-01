import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Check the signed-in user's permissions inside a component:
 *
 *     const { can } = usePermissions();
 *     {can('users.manage') && <Button>Delete</Button>}
 *
 * This only decides what to SHOW. The server checks every request again.
 */
export function usePermissions() {
    const { permissions } = usePage().props.auth;

    return {
        can: (permission) => permissions.includes(permission),
    };
}

/**
 * Send a request from a button and know when it is in flight, so the
 * button can be disabled until the server has answered:
 *
 *     const { busy, send } = useRequest();
 *     <Button disabled={busy} onClick={() => send('patch', url, { status: 'hidden' })}>
 *
 * `method` is 'post', 'put', 'patch' or 'delete'. The page keeps its
 * scroll position.
 */
export function useRequest() {
    const [busy, setBusy] = useState(false);

    const send = (method, url, data = {}, options = {}) => {
        router.visit(url, {
            method,
            data,
            preserveScroll: true,
            ...options,
            onStart: () => setBusy(true),
            onFinish: () => {
                setBusy(false);
                options.onFinish?.();
            },
        });
    };

    return { busy, send };
}

/** 95 -> "1 hr 35 min", 20 -> "20 min", 0 -> "No time" */
export function formatMinutes(minutes) {
    if (!minutes) {
        return 'No time';
    }

    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    return [hours ? `${hours} hr` : null, rest ? `${rest} min` : null]
        .filter(Boolean)
        .join(' ');
}

/**
 * Return a copy of `list` with the item at `from` moved to position `to`.
 * Returns the same list if `to` is outside it.
 */
export function moveItem(list, from, to) {
    if (to < 0 || to >= list.length) {
        return list;
    }

    const copy = [...list];
    const [item] = copy.splice(from, 1);
    copy.splice(to, 0, item);

    return copy;
}

/** "user" -> "User" */
export function capitalize(text) {
    return text ? text.charAt(0).toUpperCase() + text.slice(1) : '';
}
