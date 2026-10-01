import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/react';
import { Link } from '@inertiajs/react';

/**
 * An accessible dropdown menu (arrow keys, Escape and focus handling
 * come from Headless UI).
 *
 * <Dropdown label="Account" trigger={<Avatar user={user} />}>
 *     <Dropdown.Link href={route('profile.edit')}>Settings</Dropdown.Link>
 * </Dropdown>
 */
export default function Dropdown({ trigger, label, children }) {
    return (
        <Menu as="div" className="relative">
            <MenuButton
                aria-label={label}
                className="flex min-h-11 items-center gap-2 rounded-lg px-2 text-sm font-medium text-ink hover:bg-aubergine-soft"
            >
                {trigger}
            </MenuButton>

            <MenuItems
                anchor="bottom end"
                className="z-50 mt-2 w-56 rounded-xl border border-line bg-paper p-1 shadow-lg focus:outline-none"
            >
                {children}
            </MenuItems>
        </Menu>
    );
}

const itemClasses =
    'block w-full rounded-lg px-3 py-2.5 text-start text-sm text-ink data-[focus]:bg-aubergine-soft data-[focus]:text-aubergine';

Dropdown.Link = function DropdownLink({ children, ...props }) {
    return (
        <MenuItem>
            <Link {...props} className={itemClasses}>
                {children}
            </Link>
        </MenuItem>
    );
};

Dropdown.Divider = function DropdownDivider() {
    return <div className="my-1 border-t border-line" role="separator" />;
};
