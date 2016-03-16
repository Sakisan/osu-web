<?php

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    private $faker;

    private $improvement_speeds;

    private $common_countries;

    public function __construct()
    {
        $this->faker = $this->faker = Faker::create();
        $this->common_countries = $this->common_countries = ['US', 'JP', 'CN', 'DE', 'TW', 'RU', 'KR', 'PL', 'CA', 'FR', 'BR', 'GB', 'AU'];
        $this->improvement_speeds = $this->improvement_speeds = [
            rand(100, 102) / 100, // Slow Learner
            rand(102, 110) / 100, // Fast Learner
            rand(110, 115) / 100, // Genius / Multiaccounter
        ];
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // DB::table('phpbb_users')->delete();
        // DB::table('osu_user_stats')->delete();
        // DB::table('osu_user_stats_fruits')->delete();
        // DB::table('osu_user_stats_mania')->delete();
        // DB::table('osu_user_stats_taiko')->delete();
        // DB::table('osu_user_performance_rank')->delete();


        // Create 10 users and their stats
        factory(App\Models\User::class, 10)->create()->each(function ($user) {

            // Create rank histories for all 4 modes
            for ($mode = 0; $mode <= 3; $mode++) { // 0 = standard, 1 = taiko etc...

                $statistics = $this->createStatistics($user, $mode);

                $rank = $statistics->rank;

                $rankHistory = new App\Models\RankHistory;
                $rankHistory->mode = $mode;

                // Start with current rank, and move down (back in time) to r0
                $rankHistory->r89 = $rank;

                for ($day = 88; $day >= 0; $day--) {
                    $r = 'r' . $day;
                    $prev_r = 'r' . ($day + 1);
                    // We wouldn't expect the user to improve every day
                    $does_improve = $this->faker->boolean(rand(10, 35));
                    if ($does_improve === true) {
                        $improvement_modifier = array_rand_val($this->improvement_speeds);
                        $new_rank = round($rankHistory->$prev_r * $improvement_modifier);
                    } else {
                        $slightDecay = rand(998, 999) / 1000;
                        $new_rank = round($rankHistory->$prev_r * $slightDecay);
                    }
                    $new_rank = max($new_rank, 1);
                    $rankHistory->$r = $new_rank;
                }
                $user->rankHistories()->save($rankHistory);
            }

        });
    }

    function createStatistics($user, $mode)
    {
        if ($mode === 0) {
            $modeClass = App\Models\UserStatistics\Osu::class;
        } else if ($mode === 1) {
            $modeClass = App\Models\UserStatistics\Taiko::class;
        } else if ($mode === 2) {
            $modeClass = App\Models\UserStatistics\Fruits::class;
        } else { // if ($mode === 3) {
            $modeClass = App\Models\UserStatistics\Mania::class;
        }

        $rank = rand(1, 50000);
        $country_code = array_rand_val($this->common_countries);
        $attributes = [
            'country_acronym' => $country_code,
            'rank' => $rank,
            'rank_score_index' => $rank
        ];
        $object = factory($modeClass)->create($attributes);
        return $user->statisticsOsu()->save($object);
    }
}
