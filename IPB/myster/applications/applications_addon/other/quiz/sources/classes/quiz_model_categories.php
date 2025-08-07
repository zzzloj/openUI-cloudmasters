<?php 
class quiz_model_categories extends quiz_model_quiz {
	
	// @todo - option to sort by id rather than alphabetically
	public function findAll($limit = array(0,25), $direction = 'ASC')
	{
		$categories = array();
		$this->DB->build(
			array(
				'select'	=> '*',
				'from'		=> 'quiz_categories',
				'order'		=> 'category_display_order '.$direction,
             	'limit'		=> $limit,
			)
		);
		$cats = $this->DB->execute();
	    if ($this->DB->getTotalRows()) {
            while ($category = $this->DB->fetch($cats)) {
                $categories[] = $category;
            }
            return $categories;
        }		
	}
	
	public function findById($id)
	{
		if ($id) {
			$category = $this->DB->buildAndFetch(
				array(
					'select'	=> '*',
					'from'		=> 'quiz_categories',
					'where'		=> 'category_id='.$id,
				)
			);
			return $category;
		}
	}
	
	public function save($data)
	{
		if ($data) {
			$cat = array(
				'category_id'				=> '',
				'category_name'				=> IPSText::htmlspecialchars($data['category_name']),
				'category_image'			=> IPSText::htmlspecialchars($data['category_image']), // fix for #128
				'category_seotitle'			=> IPSText::makeSeoTitle($data['category_name']),
				'category_display_order'	=> 0,
			);
			IPSLib::doDataHooks( $cat, 'quizAddCategoryPreInsert' );
			$this->DB->insert( 'quiz_categories', $cat );
			IPSLib::doDataHooks( $cat, 'quizAddCategoryPostInsert' );
            $category_id	= $this->DB->getInsertId();
            return $category_id;
		}
	}
	
	public function update($data)
	{
		if ($data) {
			if (empty($data['category_image'])) {
				$category = $this->findById($data['category_id']);
				$data['category_image'] = $category['category_image'];
			}
			$cat = array(
				'category_id'				=> $data['category_id'],
				'category_name'				=> $data['category_name'],
				'category_image'			=> $data['category_image'],
				'category_seotitle'			=> IPSText::makeSeoTitle($data['category_name']),
				'category_display_order'	=> 0,
			);			
			$this->DB->update( "quiz_categories", $cat, 'category_id='.$data['category_id'] );     
            return true;
		}
	}
	
	public function delete($id)
	{
		if ($id) {
			$this->DB->delete('quiz_categories', 'category_id='.$id);
			return true;
		}
	}
	
    public function multiSelect()
    {
    	$cats = $this->findAll();
    	if (!empty($cats)) {
    		foreach ($cats as $cat) {
    			$category[] = array(
    				$cat['category_id'], $cat['category_name']
    			);
    		}
    		return $category;
    	}
    }
	
}
?>