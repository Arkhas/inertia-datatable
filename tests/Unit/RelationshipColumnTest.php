<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\TestModels\TestModel;
use Tests\TestModels\User;
use Tests\TestModels\Team;
use Tests\TestModels\WithTestModels;
use Tests\TestModels\TestModelDataTable;
use Arkhas\InertiaDatatable\EloquentTable;
use Arkhas\InertiaDatatable\Columns\Column;
use Illuminate\Http\Request;

class RelationshipColumnTest extends TestCase
{
    use WithTestModels;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestModels();
    }

    protected function tearDown(): void
    {
        $this->tearDownTestModels();
        parent::tearDown();
    }

    public function test_column_detects_relationship_path()
    {
        $column = Column::make('user.name');

        $this->assertTrue($column->hasRelation());
        $this->assertEquals(['user'], $column->getRelationPath());
        $this->assertEquals('name', $column->getName());
        $this->assertEquals('user.name', $column->getFullName());
    }

    public function test_column_detects_nested_relationship_path()
    {
        $column = Column::make('user.team.name');

        $this->assertTrue($column->hasRelation());
        $this->assertEquals(['user', 'team'], $column->getRelationPath());
        $this->assertEquals('name', $column->getName());
        $this->assertEquals('user.team.name', $column->getFullName());
    }

    public function test_render_html_with_relationship()
    {
        // Get a test model with its relationships
        $testModel = TestModel::with('user.team')->first();
        $this->assertNotNull($testModel->user);

        // Create a column for the user name
        $column = Column::make('user.name');

        // Render the HTML
        $html = $column->renderHtml($testModel);

        // Verify that it matches the user's name
        $this->assertEquals($testModel->user->name, $html);
    }

    public function test_render_html_with_nested_relationship()
    {
        // Get a test model with its relationships
        $testModel = TestModel::with('user.team')->first();
        $this->assertNotNull($testModel->user);
        $this->assertNotNull($testModel->user->team);

        // Create a column for the team name
        $column = Column::make('user.team.name');

        // Render the HTML
        $html = $column->renderHtml($testModel);

        // Verify that it matches the team's name
        $this->assertEquals($testModel->user->team->name, $html);
    }

    public function test_search_with_relationship_column()
    {
        $datatable = new TestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('user.name'),
        ]);

        $datatable->table($table);

        // Search for John Doe (user name)
        request()->replace(['search' => 'John']);
        $results = $datatable->getResults()->get();

        // Should find at least one result
        $this->assertNotEmpty($results);

        // Should include the test model assigned to John Doe
        $this->assertTrue($results->pluck('name')->contains('Alice'), 
            "Expected results to include Alice (assigned to John Doe)");

        // Verify that all results have an user with a name containing "John"
        foreach ($results as $result) {
            $this->assertNotNull($result->user);
            $this->assertStringContainsString('John', $result->user->name, 
                "Expected user name to contain 'John'");
        }
    }

    public function test_search_with_nested_relationship_column()
    {
        $datatable = new TestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('user.team.name'),
        ]);

        $datatable->table($table);

        // Search for Engineering team
        request()->replace(['search' => 'Engineering']);
        $results = $datatable->getResults()->get();

        // Should find the test models assigned to people in the Engineering team
        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('name')->contains('Alice'));
        $this->assertTrue($results->pluck('name')->contains('Charlie'));
    }

    public function test_order_with_relationship_column()
    {
        $datatable = new TestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('user.name'),
        ]);

        $datatable->table($table);

        // Order by user name ascending
        request()->replace(['sort' => 'user.name', 'direction' => 'asc']);
        $results = $datatable->getResults()->get();

        // Verify that we have all expected models in the results
        $this->assertCount(3, $results);
        $names = $results->pluck('name')->toArray();
        $this->assertContains('Alice', $names, "Expected Alice to be in the results");
        $this->assertContains('Bob', $names, "Expected Bob to be in the results");
        $this->assertContains('Charlie', $names, "Expected Charlie to be in the results");

        // Verify that all results have an user with a name
        foreach ($results as $result) {
            $this->assertNotNull($result->user);
            $this->assertNotNull($result->user->name);
        }
    }

    public function test_order_with_nested_relationship_column()
    {
        $datatable = new TestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('user.team.name'),
        ]);

        $datatable->table($table);

        // Order by team name ascending
        request()->replace(['sort' => 'user.team.name', 'direction' => 'asc']);
        $results = $datatable->getResults()->get();

        // Get the team names for each result
        $engineeringCount = 0;
        $marketingCount = 0;

        foreach ($results as $result) {
            $teamName = $result->user->team->name;
            if ($teamName === 'Engineering') {
                $engineeringCount++;
            } elseif ($teamName === 'Marketing') {
                $marketingCount++;
            }
        }

        // Verify that we have the expected number of results from each team
        $this->assertEquals(2, $engineeringCount, "Expected 2 results from Engineering team");
        $this->assertEquals(1, $marketingCount, "Expected 1 result from Marketing team");

        // Verify that the results contain the expected models
        $names = $results->pluck('name')->toArray();
        $this->assertContains('Alice', $names, "Expected Alice to be in the results");
        $this->assertContains('Bob', $names, "Expected Bob to be in the results");
        $this->assertContains('Charlie', $names, "Expected Charlie to be in the results");
    }

    public function test_render_html_with_null_relationship()
    {
        // Create a test model with null user
        $testModel = new TestModel(['name' => 'Test', 'status' => 'active']);
        $testModel->user = null;

        // Create a column for the user name
        $column = Column::make('user.name');

        // Render the HTML
        $html = $column->renderHtml($testModel);

        // Should return null for null relationship
        $this->assertNull($html);
    }

    public function test_render_html_with_partial_null_relationship()
    {
        // Create a test model with user but null team
        $testModel = TestModel::with('user')->first();
        $this->assertNotNull($testModel->user);

        // Set the team to null
        $testModel->user->team = null;

        // Create a column for the team name
        $column = Column::make('user.team.name');

        // Render the HTML
        $html = $column->renderHtml($testModel);

        // Should return null for null relationship
        $this->assertNull($html);
    }

    public function test_order_callback_and_apply_order()
    {
        $column = Column::make('user.name');
        $query = TestModel::query();
        $column->applyOrder($query, 'desc');
        $this->assertStringContainsString('inner join "users" on "test_models"."user_id" = "users"."id" order by "users"."name" desc', $query->toSql());
    }
}
