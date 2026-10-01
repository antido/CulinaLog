import Button from '@/Components/Button';

/** A submit button in the primary colour. */
export default function PrimaryButton({ type = 'submit', ...props }) {
    return <Button variant="primary" type={type} {...props} />;
}
