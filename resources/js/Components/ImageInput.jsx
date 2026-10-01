import InputError from '@/Components/InputError';
import { useEffect, useState } from 'react';

const MAX_BYTES = 5 * 1024 * 1024;

/**
 * A file picker for one image, with a preview.
 *
 * - `currentUrl` is the image already saved on the server, if any.
 * - `onChange(file)` receives the chosen File, or null when cleared.
 * - `onRemoveChange(bool)` is called when the user ticks "Remove the current image".
 *
 * The size check here only saves the user a wasted upload.
 * The server validates and re-saves every image.
 */
export default function ImageInput({
    id,
    label,
    currentUrl = null,
    error,
    round = false,
    onChange,
    onRemoveChange,
}) {
    const [previewUrl, setPreviewUrl] = useState(null);
    const [localError, setLocalError] = useState(null);
    const [remove, setRemove] = useState(false);

    // Release the preview's memory when it changes or the form closes.
    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    const chooseFile = (e) => {
        const file = e.target.files[0] ?? null;

        if (file && file.size > MAX_BYTES) {
            setLocalError('That image is larger than 5 MB. Choose a smaller one.');
            e.target.value = '';
            setPreviewUrl(null);
            onChange(null);
            return;
        }

        setLocalError(null);
        setPreviewUrl(file ? URL.createObjectURL(file) : null);
        onChange(file);
    };

    const toggleRemove = (e) => {
        setRemove(e.target.checked);
        onRemoveChange?.(e.target.checked);
    };

    const shownUrl = previewUrl ?? (remove ? null : currentUrl);

    return (
        <div>
            <label htmlFor={id} className="block text-sm font-medium text-ink">
                {label}
            </label>

            <div className="mt-2 flex flex-col gap-4 sm:flex-row sm:items-center">
                <div
                    className={`flex shrink-0 items-center justify-center overflow-hidden border border-line bg-rice text-xs text-plum ${
                        round ? 'size-24 rounded-full' : 'h-28 w-40 rounded-xl'
                    }`}
                >
                    {shownUrl ? (
                        <img
                            src={shownUrl}
                            alt="Selected image preview"
                            className="size-full object-cover"
                        />
                    ) : (
                        <span>No image</span>
                    )}
                </div>

                <div className="min-w-0">
                    <input
                        id={id}
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={chooseFile}
                        aria-describedby={`${id}-hint`}
                        className="block w-full text-sm text-plum file:mr-3 file:min-h-11 file:rounded-lg file:border file:border-solid file:border-line file:bg-paper file:px-4 file:text-sm file:font-semibold file:text-ink hover:file:border-aubergine"
                    />
                    <p id={`${id}-hint`} className="mt-2 text-sm text-plum">
                        JPEG, PNG or WebP, up to 5 MB.
                    </p>

                    {currentUrl && onRemoveChange && !previewUrl && (
                        <label className="mt-2 flex min-h-11 items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={remove}
                                onChange={toggleRemove}
                                className="size-6 rounded border-line text-aubergine"
                            />
                            Remove the current image
                        </label>
                    )}
                </div>
            </div>

            <InputError message={localError ?? error} className="mt-2" />
        </div>
    );
}
