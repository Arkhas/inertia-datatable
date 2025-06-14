<?php

namespace Tests\Traits;

trait WithDatatableRequest
{
    /**
     * Helper method to set the request data correctly for the datatable
     */
    protected function setDatatableRequest(array $data): void
    {
        request()->replace(['dt' => $data]);
    }
}