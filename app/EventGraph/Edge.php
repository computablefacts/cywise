<?php

namespace App\EventGraph;

class Edge
{
    public Node $from;
    public Node $to;

    public function __construct(Node $from, Node $to)
    {
        $this->from = $from;
        $this->to = $to;
    }
}
