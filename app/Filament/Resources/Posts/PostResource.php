<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Wave\Category;
use Wave\Changelog;
use Wave\Post;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static BackedEnum|string|null $navigationIcon = 'phosphor-pencil-line-duotone';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state)))
                    ->required()
                    ->maxLength(191),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(191),
                RichEditor::make('body')
                    ->required()
                    ->fileAttachmentsDisk(config('filament.default_filesystem_disk'))
                    ->fileAttachmentsDirectory('attachments')
                    ->fileAttachmentsVisibility('public')
                    ->columnSpanFull(),
                Textarea::make('excerpt')
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->image()
                    ->disk(config('filament.default_filesystem_disk'))
                    ->directory('posts'),
                TextInput::make('seo_title')
                    ->maxLength(191),
                Select::make('author_id')
                    ->label('Author')
                    ->options(
                        User::all()
                            ->mapWithKeys(fn($user) => [
                                $user->id => $user->name
                                    ?? $user->username
                                        ?? $user->email,
                            ])
                            ->toArray()
                    )
                    ->searchable()
                    ->required(),
                Select::make('category_id')
                    ->label('Category')
                    ->options(Category::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Textarea::make('meta_description')
                    ->columnSpanFull(),
                Textarea::make('meta_keywords')
                    ->columnSpanFull(),
                Select::make('status')
                    ->required()
                    ->options([
                        'DRAFT' => 'Draft',
                        'PUBLISHED' => 'Published',
                        'PENDING' => 'Pending',
                    ]),
                Toggle::make('featured')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                ImageColumn::make('image'),
                TextColumn::make('status'),
                IconColumn::make('featured')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->after(fn() => self::rebuildSitemap()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(fn() => self::rebuildSitemap()),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    public static function rebuildSitemap(): void
    {
        $sitemap = public_path('sitemap.xml');

        if (\Illuminate\Support\Facades\File::exists($sitemap)) {
            \Illuminate\Support\Facades\File::delete($sitemap);
        }

        // Initial URL map with default lastmod
        $urlToLastModified = collect();

        // Laravel Folio Pages
        $folio = resource_path('themes/cywise/pages');
        collect(\Illuminate\Support\Facades\File::allFiles($folio))
            ->filter(fn(\SplFileInfo $file) => $file->getExtension() === 'php')
            ->map(function (\SplFileInfo $file) use ($folio) {
                $uri = $file->getRelativePathname();
                if (Str::endsWith($uri, 'index.blade.php')) {
                    $uri = Str::beforeLast($uri, 'index.blade.php');
                } else {
                    $uri = Str::beforeLast($uri, '.blade.php');
                }
                return [
                    'path' => Str::trim($uri, '/'),
                    'lastmod' => date('Y-m-d'),
                ];
            })
            ->filter(fn(array $item) => !Str::contains($item['path'], ['[', ']'])) // Exclure les fichiers dynamiques Folio [...]
            ->filter(fn(array $item) => !Str::startsWith($item['path'], ['layout', 'profile', 'recipe', 'settings', 'subscription', 'pricing'])) // Exclure les répertoires privés
            ->filter(function (array $item) use ($folio) { // Exclure les fichiers utilisant le middleware('auth')
                $uri = $item['path'];
                $file = $folio . '/' . ($uri === '' ? 'index' : $uri) . '.blade.php';
                if (!\Illuminate\Support\Facades\File::exists($file)) {
                    $file = $folio . '/' . $uri . '/index.blade.php';
                }
                if (\Illuminate\Support\Facades\File::exists($file)) {
                    $content = \Illuminate\Support\Facades\File::get($file);
                    if (Str::contains($content, "middleware('auth')") || Str::contains($content, 'middleware("auth")')) {
                        return false;
                    }
                }
                return true;
            })
            ->each(fn(array $item) => $urlToLastModified->put($item['path'], $item['lastmod']));

        // Blog URLs
        $urlToLastModified->put('blog', date('Y-m-d'));
        try {
            Category::all()->each(fn(Category $c) => $urlToLastModified->put("blog/{$c->slug}", $c->updated_at?->format('Y-m-d') ?? date('Y-m-d')));
            Post::all()->each(fn(Post $p) => $urlToLastModified->put("blog/" . ($p->category->slug ?? 'all') . "/{$p->slug}", $p->updated_at?->format('Y-m-d') ?? date('Y-m-d')));
        } catch (\Exception $e) {
            Log::warning("Erreur blog: " . $e->getMessage());
        }

        // Changelog URLs
        $urlToLastModified->put('changelog', date('Y-m-d'));
        try {
            Changelog::all()->each(fn(Changelog $c) => $urlToLastModified->put("changelog/{$c->id}", $c->updated_at?->format('Y-m-d') ?? date('Y-m-d')));
        } catch (\Exception $e) {
            Log::warning("Erreur changelog: " . $e->getMessage());
        }

        // Build sitemap
        $baseUrl = Str::rtrim(config('app.url'), '/');
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

        $urlToLastModified->filter(fn($lastmod, $path) => !Str::contains($path, ['{', '}']))
            ->sortKeys()
            ->each(function (string $lastmod, string $path) use ($xml, $baseUrl) {
                $path = Str::ltrim($path, '/');
                $url = $xml->addChild('url');
                $url->addChild('loc', htmlspecialchars($baseUrl . ($path === '' ? '' : "/$path")));
                $url->addChild('lastmod', $lastmod);
                $url->addChild('changefreq', 'weekly');
                $url->addChild('priority', ($path === '' ? '1.0' : '0.8'));
            });

        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        // Update sitemap
        \Illuminate\Support\Facades\File::put($sitemap, $dom->saveXML());

        Log::debug("Sitemap généré ({$urlToLastModified->count()} URLs).");
    }
}
