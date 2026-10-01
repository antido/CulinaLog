import Button from '@/Components/Button';

/** A button for destructive actions such as "Delete". */
export default function DangerButton({ type = 'submit', ...props }) {
    return <Button variant="danger" type={type} {...props} />;
}
