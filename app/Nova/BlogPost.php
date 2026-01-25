<?php

namespace App\Nova;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\KeyValue;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class BlogPost extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\BlogPost>
     */
    public static $model = \App\Models\BlogPost::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'title', 'slug', 'excerpt',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Title')
                ->sortable()
                ->rules('required', 'max:255'),

            Slug::make('Slug')
                ->from('Title')
                ->rules('required', 'max:255')
                ->creationRules('unique:blog_posts,slug')
                ->updateRules('unique:blog_posts,slug,{{resourceId}}'),

            Textarea::make('Excerpt')
                ->rules('required', 'max:500')
                ->hideFromIndex(),

            Markdown::make('Content')
                ->rules('required')
                ->hideFromIndex(),

            new Panel('SEO', $this->seoFields()),

            new Panel('GEO Data', $this->geoFields()),

            Select::make('Status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'archived' => 'Archived',
                ])
                ->displayUsingLabels()
                ->sortable()
                ->filterable()
                ->default('draft'),

            Badge::make('Status')
                ->map([
                    'draft' => 'warning',
                    'published' => 'success',
                    'archived' => 'danger',
                ])
                ->onlyOnIndex(),

            DateTime::make('Published At')
                ->sortable()
                ->filterable()
                ->nullable(),

            BelongsTo::make('Author', 'author', User::class)
                ->sortable()
                ->filterable()
                ->nullable()
                ->default($request->user()?->id),

            Text::make('Tags')
                ->help('Comma-separated list of tags')
                ->nullable()
                ->hideFromIndex()
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                    $tags = $request->input($requestAttribute);
                    if ($tags) {
                        $model->{$attribute} = array_map('trim', explode(',', $tags));
                    } else {
                        $model->{$attribute} = [];
                    }
                })
                ->resolveUsing(function ($value) {
                    return is_array($value) ? implode(', ', $value) : $value;
                }),

            Number::make('Views', 'view_count')
                ->sortable()
                ->exceptOnForms(),

            DateTime::make('Created At')
                ->sortable()
                ->exceptOnForms(),
        ];
    }

    protected function seoFields(): array
    {
        return [
            Text::make('Meta Title')
                ->nullable()
                ->rules('nullable', 'max:70')
                ->help('Leave blank to use the post title'),

            Textarea::make('Meta Description')
                ->nullable()
                ->rules('nullable', 'max:160')
                ->help('Leave blank to use the excerpt'),

            Image::make('Featured Image')
                ->disk('public')
                ->nullable()
                ->help('Recommended size: 1200x630px. Images will be converted to PNG for social sharing compatibility.')
                ->store(function (NovaRequest $request, $model, $attribute, $requestAttribute) {
                    $file = $request->file($requestAttribute);
                    if (! $file) {
                        return null;
                    }

                    $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                    $filename = $filename . '-' . Str::random(6) . '.png';
                    $path = 'blog/' . $filename;

                    // Get the file extension to determine conversion method
                    $extension = strtolower($file->getClientOriginalExtension());

                    if ($extension === 'svg') {
                        // Use Imagick for SVG conversion
                        $imagick = new \Imagick();
                        $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
                        $imagick->readImageBlob($file->get());
                        $imagick->setImageFormat('png');
                        // Resize to 1200x630 for social sharing
                        $imagick->resizeImage(1200, 630, \Imagick::FILTER_LANCZOS, 1);
                        $imageData = $imagick->getImageBlob();
                        $imagick->destroy();
                    } else {
                        // Use GD for other image formats
                        $sourceImage = match ($extension) {
                            'jpg', 'jpeg' => imagecreatefromjpeg($file->getPathname()),
                            'gif' => imagecreatefromgif($file->getPathname()),
                            'webp' => imagecreatefromwebp($file->getPathname()),
                            'png' => imagecreatefrompng($file->getPathname()),
                            default => imagecreatefromstring($file->get()),
                        };

                        if (! $sourceImage) {
                            // Fallback: store as-is if conversion fails
                            return $file->storeAs('blog', $filename, 'public');
                        }

                        // Get original dimensions
                        $origWidth = imagesx($sourceImage);
                        $origHeight = imagesy($sourceImage);

                        // Create resized image at 1200x630
                        $newImage = imagecreatetruecolor(1200, 630);

                        // Preserve transparency
                        imagealphablending($newImage, false);
                        imagesavealpha($newImage, true);
                        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
                        imagefill($newImage, 0, 0, $transparent);

                        // Resize
                        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, 1200, 630, $origWidth, $origHeight);

                        // Save to buffer
                        ob_start();
                        imagepng($newImage, null, 9);
                        $imageData = ob_get_clean();

                        imagedestroy($sourceImage);
                        imagedestroy($newImage);
                    }

                    // Store the PNG
                    Storage::disk('public')->put($path, $imageData);

                    return [
                        $attribute => $path,
                    ];
                }),
        ];
    }

    protected function geoFields(): array
    {
        return [
            Code::make('Schema JSON', 'schema_json')
                ->json()
                ->readonly()
                ->hideFromIndex()
                ->help('Auto-generated schema.org JSON-LD for AI search engines. Updated automatically when the post is saved.'),
        ];
    }

    /**
     * Get the cards available for the resource.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array<int, \Laravel\Nova\Filters\Filter>
     */
    public function filters(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array<int, \Laravel\Nova\Lenses\Lens>
     */
    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
