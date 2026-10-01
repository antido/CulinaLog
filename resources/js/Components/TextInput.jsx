import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export const inputClasses =
    'rounded-lg border-line bg-paper text-ink shadow-sm placeholder:text-plum disabled:bg-rice disabled:text-plum';

export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props },
    ref,
) {
    const localRef = useRef(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            className={`${inputClasses} ${className}`}
            ref={localRef}
        />
    );
});
