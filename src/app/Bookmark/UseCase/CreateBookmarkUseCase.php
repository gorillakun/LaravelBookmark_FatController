<?php

namespace APP\Bookmark\UseCase;

use App\Lib\LinkPreview\LinkPreview;
use App\Lib\LinkPreview\LinkPreviewInterface;
use App\Models\Bookmark;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class CreateBookmarkUseCase
{
    private LinkPreviewInterface $linkPreview;

    public function __construct(LinkPreviewInterface $linkPreview)
    {
        $this->linkPreview = $linkPreview;
    }

    public function handle(string $url, int $category, string $comment)
    {

        // 下記のサービスでも同様のことが実現できる
        // @see https://www.linkpreview.net/
        try {
            $preview = $this->linkPreview->get($url);

            $model = new Bookmark();
            $model->url = $url;
            $model->category_id = $category;
            $model->user_id = Auth::id();
            $model->comment = $comment;
            $model->page_title = $preview->title;
            $model->page_description = $preview->description;
            $model->page_thumbnail_url = $preview->cover;
            $model->save();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw ValidationException::withMessages([
                'url' => 'URLが存在しない等の理由で読み込めませんでした。変更して再度投稿してください'
            ]);
        }
    }

}

