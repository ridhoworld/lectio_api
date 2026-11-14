<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\Book;
use App\Models\ReadingGoal;
use App\Models\ReadingSession;
use App\Models\User;
use App\Models\UserBook;
use App\Models\UserStreak;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LectioSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Badges
        $badges = [
            [
                'name' => 'Kutu Buku Pemula',
                'description' => 'Selesaikan buku pertamamu di Lectio.',
                'requirement_type' => 'books_finished',
                'requirement_value' => 1,
            ],
            [
                'name' => 'Pembaca Setia',
                'description' => 'Pertahankan kebiasaan membaca selama 7 hari berturut-turut.',
                'requirement_type' => 'streak_days',
                'requirement_value' => 7,
            ],
            [
                'name' => 'Maraton Halaman',
                'description' => 'Tembus total 500 halaman membaca yang tercatat.',
                'requirement_type' => 'pages_read',
                'requirement_value' => 500,
            ],
            [
                'name' => 'Kolektor Kutipan',
                'description' => 'Simpan minimal 5 kutipan berkesan dari bacaanmu.',
                'requirement_type' => 'notes_count',
                'requirement_value' => 5,
            ],
            [
                'name' => 'Bibliophile Sejati',
                'description' => 'Selesaikan 5 buku dalam rak pribadimu.',
                'requirement_type' => 'books_finished',
                'requirement_value' => 5,
            ],
        ];

        foreach ($badges as $b) {
            Badge::updateOrCreate(['name' => $b['name']], $b);
        }

        // 2. Seed Master Books
        $books = [
            [
                'title' => 'Filosofi Teras',
                'author' => 'Henry Manampiring',
                'publisher' => 'Kompas Gramedia',
                'total_pages' => 320,
                'genre' => 'Self Development / Filsafat',
                'isbn' => '9786024125189',
                'synopsis' => 'Penerapan filsafat Stoa kuno untuk mental yang tangguh dan tenang menghadapi dinamika kehidupan modern.',
                'cover_url' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=400&q=80',
            ],
            [
                'title' => 'Atomic Habits',
                'author' => 'James Clear',
                'publisher' => 'Gramedia Pustaka Utama',
                'total_pages' => 352,
                'genre' => 'Self Improvement',
                'isbn' => '9786020633176',
                'synopsis' => 'Perubahan kecil yang memberikan hasil luar biasa dalam membangun kebiasaan baik dan menghilangkan kebiasaan buruk.',
                'cover_url' => 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?w=400&q=80',
            ],
            [
                'title' => 'Laut Bercerita',
                'author' => 'Leila S. Chudori',
                'publisher' => 'Kepustakaan Populer Gramedia',
                'total_pages' => 379,
                'genre' => 'Fiksi Sejarah',
                'isbn' => '9786024246945',
                'synopsis' => 'Kisah persahabatan, cinta, dan kehilangan aktivis mahasiswa yang hilang di masa reformasi 1998.',
                'cover_url' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=400&q=80',
            ],
            [
                'title' => 'Bumi Manusia',
                'author' => 'Pramoedya Ananta Toer',
                'publisher' => 'Lentera Dipantara',
                'total_pages' => 535,
                'genre' => 'Sastra / Sejarah',
                'isbn' => '9789799731234',
                'synopsis' => 'Roman tetralogi Buru yang mengisahkan pergulatan pemikiran dan cinta Minke di awal abad ke-20.',
                'cover_url' => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=400&q=80',
            ],
            [
                'title' => 'The Psychology of Money',
                'author' => 'Morgan Housel',
                'publisher' => 'Harriman House',
                'total_pages' => 256,
                'genre' => 'Finance & Psychology',
                'isbn' => '9780857197689',
                'synopsis' => 'Pelajaran abadi mengenai kekayaan, keserakahan, dan kebahagiaan serta bagaimana pola pikir memengaruhi finansial.',
                'cover_url' => 'https://images.unsplash.com/photo-1592496431122-2349e0fbc666?w=400&q=80',
            ],
        ];

        $createdBooks = [];
        foreach ($books as $item) {
            $createdBooks[] = Book::updateOrCreate(['title' => $item['title']], $item);
        }

        // 3. Demo User
        $user = User::updateOrCreate(
            ['email' => 'demo@lectio.app'],
            [
                'username' => 'rakapembaca',
                'name' => 'Raka Pratama',
                'password' => Hash::make('password123'),
                'timezone' => 'Asia/Jakarta',
                'reminder_time' => '20:30',
            ]
        );

        $currentYear = Carbon::now()->year;

        // Streak
        UserStreak::updateOrCreate(
            ['user_id' => $user->id],
            [
                'current_streak' => 5,
                'longest_streak' => 14,
                'last_read_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                'freeze_count' => 2,
            ]
        );

        // Reading Goals
        ReadingGoal::updateOrCreate(
            ['user_id' => $user->id, 'goal_type' => 'daily_minutes', 'year' => $currentYear],
            ['target_value' => 20, 'is_active' => true]
        );
        ReadingGoal::updateOrCreate(
            ['user_id' => $user->id, 'goal_type' => 'daily_pages', 'year' => $currentYear],
            ['target_value' => 25, 'is_active' => true]
        );
        ReadingGoal::updateOrCreate(
            ['user_id' => $user->id, 'goal_type' => 'yearly_books', 'year' => $currentYear],
            ['target_value' => 12, 'is_active' => true]
        );

        // User Books for Demo User
        if (count($createdBooks) >= 3) {
            // Book 1: Reading
            $ub1 = UserBook::updateOrCreate(
                ['user_id' => $user->id, 'book_id' => $createdBooks[0]->id],
                [
                    'status' => 'reading',
                    'format' => 'physical',
                    'current_page' => 124,
                    'started_at' => Carbon::now()->subDays(6)->toDateString(),
                ]
            );

            // Record reading session for today
            ReadingSession::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'user_book_id' => $ub1->id,
                    'session_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                ],
                [
                    'start_page' => 104,
                    'end_page' => 124,
                    'duration_minutes' => 25,
                ]
            );

            // Book 2: Want to read
            UserBook::updateOrCreate(
                ['user_id' => $user->id, 'book_id' => $createdBooks[1]->id],
                [
                    'status' => 'want_to_read',
                    'format' => 'ebook',
                    'current_page' => 0,
                ]
            );

            // Book 3: Finished
            UserBook::updateOrCreate(
                ['user_id' => $user->id, 'book_id' => $createdBooks[2]->id],
                [
                    'status' => 'finished',
                    'format' => 'physical',
                    'current_page' => 379,
                    'rating' => 4.8,
                    'review' => 'Karya sastra yang begitu menyentuh hati dan memikat dari halaman awal hingga akhir.',
                    'started_at' => Carbon::now()->subMonths(1)->toDateString(),
                    'finished_at' => Carbon::now()->subDays(8)->toDateString(),
                ]
            );
        }
    }
}

