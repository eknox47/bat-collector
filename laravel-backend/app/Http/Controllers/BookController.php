<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\StoreBookRequest;
use App\Services\PenguinRandomHouseService;
use App\Models\Book;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'max:255'],
        ]);

        $response = Http::get(
            'https://api.penguinrandomhouse.com/resources/v2/title/domains/'
                . config('services.penguin_random_house.domain') . '/search',
            [
                'api_key' => config('services.penguin_random_house.key'),
                'q' => $validated['search'],
            ]
        );

        $titles = $response->json('data.results');

        if (! $titles) {
            return [];
        }

        return collect($titles)
            ->map(function ($item) {
                return [
                    'key' => $item['key'],
                    'title' => $item['name'],
                    'coverUrl' => $this->coverUrlForWork($item['key']),
                ];
            })
            ->values();
    }

    private function coverUrlForWork(string $workId): ?string
    {
        $response = Http::get(
            'https://api.penguinrandomhouse.com/resources/v2/title/domains/'
                . config('services.penguin_random_house.domain') . "/works/{$workId}/titles",
            ['api_key' => config('services.penguin_random_house.key')]
        );

        if ($response->failed()) {
            return null;
        }

        $title = $response->json('data.titles.0');

        return collect($title['_links'] ?? [])->firstWhere('rel', 'icon')['href'] ?? null;
    }

    public function details(string $workId)
    {
        $response = Http::get(
            'https://api.penguinrandomhouse.com/resources/v2/title/domains/'
                . config('services.penguin_random_house.domain') . "/works/{$workId}/titles",
            ['api_key' => config('services.penguin_random_house.key')]
        );

        if ($response->failed()) {
            return response()->json([
                'message' => 'Unable to retrieve book variants from Penguin Random House.',
            ], $response->status());
        }

        $details = $response->json()['data']['titles'][0];

        $formattedBookDetails = [
            'isbn' => $details['isbn'],
            'title' => $details['title'],
            'author' => $details['author'],
            'onSaleDate' => $details['onsale'],
            'priceUSD' => collect($details['price'] ?? [])->firstWhere('currencyCode', 'USD')['amount'] ?? null,
            'priceCAD' => collect($details['price'] ?? [])->firstWhere('currencyCode', 'CAD')['amount'] ?? null,
            'publisher' => $details['publisher']['description'],
            'pages' => $details['pages'],
            'trim' => $details['trim'],
            'format' => $details['consumerFormat'],
            'workId' => $details['workId'],
            'focDate' => $details['focDate'],
            'coverUrl' => $this->coverUrlForWork($details['workId'])
        ];

        return $formattedBookDetails;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'isbn' => ['string']
        ]);

        //get the book info from PRH
        $response = Http::get(
            'isbn' . $validated['isbn'],
            [
                'key' => config('services.penguin_random_house.key'),
                'domain' => config('services.penguin_random_house.domain')
            ]
        );

        if($response->failed()) {
            return [
                "message" => "Server error"
            ];
        }

        $penguinRandomHouseService = new PenguinRandomHouseService();
        $mappedBook = $penguinRandomHouseService->mapPRHBooks($response->json());

        $book = Book::firstOrCreate($mappedBook);

        return $book;
    }

    public function show(string $id)
    { 
        return Book::where('id', $id)->with('authors');
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
