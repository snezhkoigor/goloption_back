<?php

namespace App\Http\Controllers\Api\V1\News;

use App\Exceptions\SystemErrorException;
use App\News;
use App\Repositories\NewsRepository;
use App\Services\RichTextService;
use App\Transformers\NewsTransformer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewsController extends Controller
{
	private $rich_text_service;

	public function __construct(RichTextService $rich_text_service)
	{
		$this->rich_text_service = $rich_text_service;
	}

	public function rules()
	{
		return [
			'title' => 'required',
			'text' => 'required'
		];
	}

	public function messages()
	{
		return [
			'title.required' => 'Заполните заголовок',
			'text.required' => 'Заполните текст',
		];
	}

    public function getNews(Request $request)
    {
	    $filters = $this->getFilters($request);
	    $sorts = $this->getSortParameters($request);
	    $search_string = $this->getSearchString($request);
	    $fieldsets = $this->getFieldsets($request);
	    $includes = $this->getIncludes($request);
	    $limit = $this->getPaginationLimit($request);
	    $offset = $this->getPaginationOffset($request);

	    $relations = $this->getRelationsFromIncludes($request);

	    $news = NewsRepository::getNews($filters, $sorts, $relations, ['*'], $search_string, $limit, $offset);

	    $meta = [
		    'count' => NewsRepository::getNewsCount($filters, $search_string),
	    ];

	    return fractal($news, new NewsTransformer())
		    ->parseIncludes($includes)
		    ->parseFieldsets($fieldsets)
		    ->addMeta($meta)
		    ->respond();
    }

	public function getNewsById($news_id)
	{
		$news = News::find($news_id);
		if ($news === null) {
			throw new NotFoundHttpException('Нет новости');
		}

		return fractal($news, new NewsTransformer())
			->respond();
	}

	public function view()
	{
		return fractal(NewsRepository::getNews(), new NewsTransformer())
			->toArray();
	}

	public function show($news_id)
	{
		$news = News::find($news_id);
		if ($news === null) {
			throw new NotFoundHttpException('Нет новости');
		}

		return fractal($news, new NewsTransformer())
			->respond();
	}

    public function add(Request $request)
    {
    	$this->validate($request, $this->rules(), $this->messages());

    	try
	    {
		    $news = new News();
		    $news->fill($request->all());
		    $news->text = $this->rich_text_service->getProcessedNewsText($request->get('text'));
		    $news->active = $request->get('active', true);
		    $news->save();
	    }
	    catch (\Exception $e)
	    {
		    throw new SystemErrorException('Ошибка добавления новости', $e);
	    }

	    return response()->json(['data' => null], Response::HTTP_NO_CONTENT);
    }

    public function updateById(Request $request, $news_id)
    {
	    $this->validate($request, $this->rules(), $this->messages());

	    $news = News::find($news_id);
	    if ($news === null) {
		    throw new NotFoundHttpException('Нет новости');
	    }

	    try
	    {
		    $news->fill($request->all());
		    $news->active = $request->get('active');
		    $news->text = $this->rich_text_service->getProcessedNewsText($request->get('text'));
		    $news->save();
	    }
	    catch (\Exception $e)
	    {
		    throw new SystemErrorException('Ошибка редактирования новости', $e);
	    }

	    return response()->json(['data' => null], Response::HTTP_NO_CONTENT);
    }

    public function deleteById($news_id)
    {
	    $news = News::find($news_id);
	    if ($news === null) {
		    throw new NotFoundHttpException('Нет новости');
	    }

	    try
	    {
		    $news->is_delete = true;
		    $news->save();
	    }
	    catch (\Exception $e)
	    {
		    throw new SystemErrorException('Ошибка удаления новости', $e);
	    }

	    return response()->json(['data' => null], Response::HTTP_NO_CONTENT);
    }
}
