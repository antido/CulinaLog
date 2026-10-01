import Button from '@/Components/Button';

/** A quiet button for secondary actions such as "Cancel". */
export default function SecondaryButton({ type = 'button', ...props }) {
    return <Button variant="secondary" type={type} {...props} />;
}
