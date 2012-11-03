<?php

interface ViewInterface {
	public function render();
	public function handle();
	public function statistics( $step, $booklet );
	public function virtualbooklet( $step, $booklet );
	public function mastersheet( $step, $booklets );
}

?>