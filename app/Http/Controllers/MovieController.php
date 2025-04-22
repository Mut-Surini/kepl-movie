<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovieRequest;
use App\Models\Movie;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class MovieController extends Controller
{
    const PAGINATION_HOME = 6;
    const PAGINATION_ADMIN = 10;
    const COVER_STORAGE = 'public';
    const COVER_PATH = 'movie_covers';
    const IMAGE_PATH = 'images';

    public function index()
    {
        $movies = Movie::latest()
            ->filter(request(['search']))
            ->paginate(self::PAGINATION_HOME)
            ->withQueryString();

        return view('homepage', compact('movies'));
    }

    public function detail($id)
    {
        $movie = Movie::findOrFail($id);
        return view('detail', compact('movie'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('input', compact('categories'));
    }

    public function store(StoreMovieRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('foto_sampul')) {
            $validated['foto_sampul'] = $request->file('foto_sampul')
                ->store(self::COVER_PATH, self::COVER_STORAGE);
        }

        Movie::create($validated);

        return redirect()
            ->route('movies.index')
            ->with('success', 'Film berhasil ditambahkan.');
    }

    public function data()
    {
        $movies = Movie::latest()->paginate(self::PAGINATION_ADMIN);
        return view('data-movies', compact('movies'));
    }

    public function form_edit($id)
    {
        $movie = Movie::findOrFail($id);
        $categories = Category::all();
        return view('form-edit', compact('movie', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'sinopsis' => 'required|string',
            'tahun' => 'required|integer',
            'pemain' => 'required|string',
            'foto_sampul' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect("/movies/edit/{$id}")
                ->withErrors($validator)
                ->withInput();
        }

        $movie = Movie::findOrFail($id);
        $updateData = $request->only(['judul', 'sinopsis', 'category_id', 'tahun', 'pemain']);

        if ($request->hasFile('foto_sampul')) {
            $fileName = $this->storeImage($request->file('foto_sampul'));

            if ($movie->foto_sampul) {
                $this->deleteImage($movie->foto_sampul);
            }

            $updateData['foto_sampul'] = $fileName;
        }

        $movie->update($updateData);

        return redirect('/movies/data')
            ->with('success', 'Data berhasil diperbarui');
    }

    public function delete($id)
    {
        $movie = Movie::findOrFail($id);

        if ($movie->foto_sampul) {
            $this->deleteImage($movie->foto_sampul);
        }

        $movie->delete();

        return redirect('/movies/data')
            ->with('success', 'Data berhasil dihapus');
    }

    protected function storeImage($file)
    {
        $randomName = uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path(self::IMAGE_PATH), $randomName);
        return $randomName;
    }

    protected function deleteImage($filename)
    {
        $path = public_path(self::IMAGE_PATH . '/' . $filename);
        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
