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

  public function testWhenFetchMetaFailed()
    {
        $url = 'https://notfound.example.com/';
        $category = BookmarkCategory::query()->first()->id;
        $comment = 'テスト用のコメント';

        // これまでと違ってMockeryというライブラリでモックを用意する
        $mock = \Mockery::mock(LinkPreviewInterface::class);

        // 作ったモックがgetメソッドを実行したら必ず例外を投げるように仕込む
        $mock->shouldReceive('get')
            ->withArgs([$url])
            ->andThrow(new \Exception('URLからメタ情報の取得に失敗'))
            ->once();

        // サービスコンテナに$mockを使うように命令する
        $this->app->instance(
            LinkPreviewInterface::class,
            $mock
        );

        // 例外が投げられることのテストは以下のように書く
        $this->expectException(ValidationException::class);
        $this->expectExceptionObject(ValidationException::withMessages([
            'url' => 'URLが存在しない等の理由で読み込めませんでした。変更して再度投稿してください'
        ]));

        // 仕込みが終わったので実際の処理を実行
        $this->useCase = $this->app->make(CreateBookmarkUseCase::class);
        $this->useCase->handle($url, $category, $comment);
    }

}