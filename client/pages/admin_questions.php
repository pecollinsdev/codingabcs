    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <?php
            echo $cardBuilder->build([
                'title'   => 'Search & Filter',
                'content' => '
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" id="questionSearch" class="form-control" placeholder="Search questions...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="questionFilter">
                                <option value="all">All Questions</option>
                                <option value="active">Active Only</option>
                                <option value="inactive">Inactive Only</option>
                            </select>
                        </div>
                    </div>
                ',
                'classes' => 'search-filter-card',
                'hover'   => false
            ]);
            ?>
        </div>
    </div> 