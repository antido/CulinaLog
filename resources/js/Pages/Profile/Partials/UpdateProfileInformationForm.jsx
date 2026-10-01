import ImageInput from '@/Components/ImageInput';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import Textarea from '@/Components/Textarea';
import TextInput from '@/Components/TextInput';
import { Link, useForm, usePage } from '@inertiajs/react';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    bio,
    className = '',
}) {
    const user = usePage().props.auth.user;

    const { data, setData, post, errors, processing, recentlySuccessful } =
        useForm({
            // Browsers can only upload files with POST, so the form is sent
            // as POST and Laravel treats it as PATCH because of this field.
            _method: 'patch',
            name: user.name,
            username: user.username,
            bio: bio ?? '',
            email: user.email,
            avatar: null,
            remove_avatar: false,
        });

    const submit = (e) => {
        e.preventDefault();

        post(route('profile.update'), { preserveScroll: true });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-xl font-semibold">Profile</h2>

                <p className="mt-1 text-sm text-plum">
                    Your name, username, photo and bio are shown on your{' '}
                    <Link href={route('users.show', user.username)} className="link">
                        public profile
                    </Link>
                    . Your email address is never shown.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <ImageInput
                    id="avatar"
                    label="Profile photo"
                    round
                    currentUrl={user.avatar_url}
                    error={errors.avatar}
                    onChange={(file) => setData('avatar', file)}
                    onRemoveChange={(remove) => setData('remove_avatar', remove)}
                />

                <div>
                    <InputLabel htmlFor="name" value="Name" />

                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        autoComplete="name"
                    />

                    <InputError className="mt-2" message={errors.name} />
                </div>

                <div>
                    <InputLabel htmlFor="username" value="Username" />

                    <TextInput
                        id="username"
                        className="mt-1 block w-full"
                        value={data.username}
                        onChange={(e) =>
                            setData('username', e.target.value.toLowerCase())
                        }
                        required
                        autoComplete="username"
                        aria-describedby="username-hint"
                    />
                    <p id="username-hint" className="mt-2 text-sm text-plum">
                        3 to 30 lowercase letters, numbers or underscores.
                    </p>

                    <InputError className="mt-2" message={errors.username} />
                </div>

                <div>
                    <InputLabel htmlFor="bio" value="Bio" />

                    <Textarea
                        id="bio"
                        className="mt-1 block w-full"
                        value={data.bio}
                        maxLength={500}
                        onChange={(e) => setData('bio', e.target.value)}
                        aria-describedby="bio-hint"
                    />
                    <p id="bio-hint" className="mt-2 text-sm text-plum">
                        Up to 500 characters. {500 - data.bio.length} left.
                    </p>

                    <InputError className="mt-2" message={errors.bio} />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="email"
                    />

                    <InputError className="mt-2" message={errors.email} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="text-sm text-ink">
                            Your email address is unverified.{' '}
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="link text-sm"
                            >
                                Send the verification email again
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-basil">
                                A new verification link has been sent to your
                                email address.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>
                        {processing ? 'Saving…' : 'Save profile'}
                    </PrimaryButton>

                    <p
                        role="status"
                        className="text-sm font-medium text-basil"
                    >
                        {recentlySuccessful ? 'Profile saved.' : ''}
                    </p>
                </div>
            </form>
        </section>
    );
}
