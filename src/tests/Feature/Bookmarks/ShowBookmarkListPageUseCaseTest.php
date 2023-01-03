<?php

namespace Tests\Feature\Bookmarks;

use App\Bookmark\UseCase\ShowBookmarkListPageUseCase;
use App\Lib\LinkPreview\LinkPreviewInterface;
use App\Models\BookmarkCategory;
use Tests\TestCase;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Validation\ValidationException;

class ShowBookmarkListPageUseCaseTest extends TestCase
{
  private ShowBookmarkListPageUseCase $useCase;

  protected function setUp(): void
  {
    parent::setUp();

    $this->app->bind(LinkPreviewInterface::class, MockLinkPreview::class);
    $this->useCase = $this->app->make(CreateBookmarkUseCase::class);
  }

  public function testResponseIsCorrect()
  {
    SEOTools::shouldReceive('setTitle')->withArgs(['ブックマーク一覧'])->once();
    SEOTools::shouldReceive('setDescription')->withArgs(['技術分野に特化したブックマーク一覧です。みんなが投稿した技術分野のブックマークが投稿順に並んでいます。HTML、CSS、JavaScript、Rust、Goなど、気になる分野のブックマークに絞って調べることもできます'])->once();
    $response = $this->useCase->handle();
    
    self::assertCount(10, $response['bookmarks']);
    self::assertCount(10, $response['top_categories']);
    self::assertCount(10, $response['top_users']);

    for ($i = 1; $i < 10; $i++) {
      self::assertSame($i, $response['top_users'][$i - 1]->id);
    }
  }

}