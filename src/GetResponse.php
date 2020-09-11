<?php
namespace PepperjamAPI;

class GetResponse extends AbstractResponse {
	private $totalResults = 0;
	
	private $totalPages = 0;
	
    private $next = null;
    
    private $previous = null;

    public function hasNext() {
        return $this->next != null;
    }

    public function hasPrevious() {
        return $this->next != null;
    }

    public function getTotalResults() {
        return $this->totalResults;
    }

    public function getTotalPages() {
        return $this->totalPages;
    }

    public function __construct($headers, $body) {
        parent::__construct($headers, $body);

        if($this->meta && isset($this->meta['pagination'])) {
            $this->totalResults = $this->meta['total_results'];
            $this->totalPages = $this->meta['total_pages'];
            if(isset($this->meta['next'])) $this->next = $this->meta['next']['href'];
            if(isset($this->meta['previous'])) $this->previous = $this->meta['previous']['href'];
        }
    }
}