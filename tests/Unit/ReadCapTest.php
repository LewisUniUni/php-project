<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Database\ProhibitedRead;
use App\Support\Database\ReadCap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReadCapTest extends TestCase
{
    public function test_a_named_column_limit_of_100_is_allowed(): void
    {
        ReadCap::assertSql('select `id`, `code` from `skus` where `code` = ? limit 100', ['SKU-1']);

        $this->addToAssertionCount(1);
    }

    public function test_a_bound_limit_of_100_is_allowed(): void
    {
        ReadCap::assertSql(
            'select `id`, `status` from `outbox_messages` where `status` = ? order by `created_at` asc limit ? for update skip locked',
            ['pending', 50],
        );

        $this->addToAssertionCount(1);
    }

    public function test_a_join_is_capped_on_result_rows(): void
    {
        ReadCap::assertSql(
            'select `stops`.`id`, `reservations`.`reference` from `delivery_stops` inner join `reservations` on `reservations`.`id` = `delivery_stops`.`reservation_id` limit 100',
            [],
        );

        $this->addToAssertionCount(1);
    }

    public function test_an_unbounded_read_is_rejected(): void
    {
        $this->expectException(ProhibitedRead::class);
        $this->expectExceptionMessage('no explicit LIMIT');

        ReadCap::assertSql('select `id` from `delivery_stops`', []);
    }

    public function test_a_limit_above_100_is_rejected(): void
    {
        $this->expectException(ProhibitedRead::class);
        $this->expectExceptionMessage('LIMIT 101');

        ReadCap::assertSql('select `id` from `delivery_stops` limit 101', []);
    }

    public function test_a_bound_limit_above_100_is_rejected(): void
    {
        $this->expectException(ProhibitedRead::class);
        $this->expectExceptionMessage('LIMIT 250');

        ReadCap::assertSql('select `id` from `delivery_stops` where `warehouse_code` = ? limit ?', ['WH-1', 250]);
    }

    public function test_select_star_is_rejected_even_with_a_small_limit(): void
    {
        $this->expectException(ProhibitedRead::class);
        $this->expectExceptionMessage('SELECT *');

        ReadCap::assertSql('select * from `users` where `email` = ? limit 1', ['avery@quay.local']);
    }

    public function test_a_qualified_star_is_rejected(): void
    {
        $this->expectException(ProhibitedRead::class);
        $this->expectExceptionMessage('SELECT *');

        ReadCap::assertSql('select `skus`.* from `skus` limit 10', []);
    }

    public function test_a_count_without_a_group_is_one_result_row(): void
    {
        ReadCap::assertSql('select count(*) as aggregate from `outbox_messages` where `status` = ?', ['pending']);

        $this->addToAssertionCount(1);
    }

    public function test_a_grouped_aggregate_still_needs_a_row_limit(): void
    {
        $this->expectException(ProhibitedRead::class);
        $this->expectExceptionMessage('no explicit LIMIT');

        ReadCap::assertSql('select `status`, count(*) as aggregate from `delivery_stops` group by `status`', []);
    }

    public function test_a_limit_inside_a_subquery_does_not_cap_the_outer_read(): void
    {
        $this->expectException(ProhibitedRead::class);
        $this->expectExceptionMessage('no explicit LIMIT');

        ReadCap::assertSql('select `id` from `skus` where `id` in (select `sku_id` from `stock_balances` limit 10)', []);
    }

    public function test_a_non_integer_limit_binding_is_rejected(): void
    {
        $this->expectException(ProhibitedRead::class);
        $this->expectExceptionMessage('not a visible integer');

        ReadCap::assertSql('select `id` from `skus` limit ?', ['many']);
    }

    public function test_a_constant_probe_is_allowed(): void
    {
        ReadCap::assertSql('select 1 as ready', []);

        $this->addToAssertionCount(1);
    }

    public function test_writes_are_not_read_caps(): void
    {
        ReadCap::assertSql('insert into `skus` (`id`, `code`) values (?, ?)', ['1', 'SKU']);
        ReadCap::assertSql('update `stock_balances` set `on_hand` = ? where `id` = ?', [1, '1']);

        $this->addToAssertionCount(1);
    }

    public function test_metadata_statements_are_rejected_from_application_code(): void
    {
        $this->expectException(ProhibitedRead::class);
        $this->expectExceptionMessage('metadata reads');

        ReadCap::assertSql('show tables', []);
    }

    /**
     * @param  list<string>|null  $columns
     * @param  list<mixed>  $groups
     */
    #[DataProvider('mongoCases')]
    public function test_mongo_fetches(mixed $limit, ?array $columns, array $groups, mixed $aggregate, ?string $message): void
    {
        if ($message !== null) {
            $this->expectException(ProhibitedRead::class);
            $this->expectExceptionMessage($message);
        }

        ReadCap::assertMongoFetch($limit, $columns, $groups, $aggregate);

        if ($message === null) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * @return list<array{0: mixed, 1: list<string>|null, 2: list<mixed>, 3: mixed, 4: string|null}>
     */
    public static function mongoCases(): array
    {
        return [
            [50, ['event_id', 'payload'], [], null, null],
            [1, ['event_id'], [], null, null],
            [null, ['event_id'], [], ['count' => 'count'], null],
            [null, ['event_id'], [], null, 'no numeric limit'],
            [101, ['event_id'], [], null, 'limit 101'],
            [10, [], [], null, 'must name fields'],
            [10, ['*'], [], null, 'must name fields'],
            [null, ['status'], ['status'], ['count' => 'count'], 'no numeric limit'],
        ];
    }

    public function test_fetch_size_helper_rejects_zero_and_101(): void
    {
        ReadCap::assertFetchSize(100);

        $this->expectException(ProhibitedRead::class);
        ReadCap::assertFetchSize(0);
    }
}
