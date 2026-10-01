<?php

namespace App\Models;

use App\Services\ImageService;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Roles are never mass assignable: they are not columns on this table and
 * can only be changed through assignRole() / syncRoles() in server code.
 * The avatar path is also set only by server code, after ImageService
 * has processed the upload.
 */
#[Fillable(['name', 'username', 'email', 'bio', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Remove the stored avatar file when an account is deleted.
     */
    protected static function booted(): void
    {
        // The database removes the user's recipes when the account is deleted.
        // Deleting them here first lets each recipe clean up its image file.
        static::deleting(function (User $user): void {
            $user->recipes()->get()->each->delete();
        });

        static::deleted(function (User $user): void {
            app(ImageService::class)->delete($user->avatar_path);
        });
    }

    /**
     * @return HasMany<Recipe, $this>
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<ForumTopic, $this>
     */
    public function forumTopics(): HasMany
    {
        return $this->hasMany(ForumTopic::class);
    }

    /**
     * @return HasMany<ForumReply, $this>
     */
    public function forumReplies(): HasMany
    {
        return $this->hasMany(ForumReply::class);
    }

    /**
     * The address of the user's profile image, or null if they have none.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => ImageService::url($this->avatar_path));
    }

    /**
     * The few details about a user that anyone may see next to their
     * recipes, comments and forum posts. Use this instead of sending
     * the whole model to the browser.
     *
     * @return array{id: int, name: string, username: string, avatar_url: string|null}
     */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'avatar_url' => $this->avatar_url,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
