<?php

use Twig\Environment;

class data {
	
	const IKKE_SVAR = 'Ikke svart';
	
	var $num_rows = 0;
	
	public function __construct( $worksheet ) {
		$this->worksheet = $worksheet;
		$this->num_rows = $this->worksheet->getHighestRow();
	}
	
	public function getNumRows() {
		return $this->num_rows;
	}
	
	public function getCell( $coord ) {
		return $this->worksheet->getCell( $coord );
	}
	public function getValue( $coord ) {
		return $this->sanValue( $this->getCell( $coord )->getValue() );
	}
	
	public function getHeader( $col ) {
		global $SHEET_DATA;
	}
	
	public function sanValue( $value ) {
		return empty( $value ) ? $this->IKKE_SVAR : $value;
	}

	/**
	 * @param Strin $col kolonne som skal telles opp
	 * @param Bool $visKunSvar vis/ignorer "ikke svart"
	 * @param Bool|String $filter $visKunSvar for 2 kolonner. Angir hvilken kolonne
	**/
	public function countOccurences( $col, $visKunSvar=false, $filter=false ) {
		$count = [];
		foreach( $this->getAlternatives( $col, $visKunSvar ) as $alternative ) {
			$count[ $alternative ] = 0;
		}

		for( $row=2; $row <= $this->getNumRows(); $row ++ ) {
			$value = $this->getValue( $col.$row );
			
			if( $filter != false && ($value == $this->IKKE_SVAR || $this->getValue( $filter.$row ) == $this->IKKE_SVAR ) ) {
				continue;
			} elseif( $visKunSvar && $value == $this->IKKE_SVAR ) {
				continue;
			}
			$count[ $value ]++;
		}
		
		$sortMethod = $this->getSortMethod( $count );
		$sortMethod( $count );
		
		return $count;
	}
	
	public function countOccurencesGroupBy( $col, $groupCol, $visKunSvar=false ) {
		$opt_count = [];
		foreach( $this->getAlternatives( $col, $visKunSvar ) as $option ) {
			$opt_count[ $option ] = 0;
		}
		$sortMethod = $this->getSortMethod( $opt_count );
		$sortMethod( $opt_count );
		
		$filter_occurrences = $this->countOccurences( $groupCol, $visKunSvar );
		$filters = array_keys( $filter_occurrences );
		
		$data = [];
		foreach( $filters as $filter ) {
			$data[ $filter ] = $opt_count;
		}
		
		for( $row=2; $row <= $this->getNumRows(); $row ++ ) {
			$value = $this->getValue( $col.$row );
			$key = $this->getValue( $groupCol.$row );
			
			if( $visKunSvar && ( $value == $this->IKKE_SVAR || $key == $this->IKKE_SVAR ) ) {
				continue;
			}
			$data[ $key ][ $value ]++;
		}

		$data['Total'] = $this->countOccurences( $col, $visKunSvar, $groupCol );
		return $data;
	}
	
	public function getAlternatives( $col, $visKunSvar=false ) {
		$alternatives = [];
		for( $row=2; $row <= $this->getNumRows(); $row ++ ) {
			$value = $this->getValue( $col.$row );
			if( $visKunSvar && $value == $this->IKKE_SVAR ) {
				continue;
			}
			if( !in_array( $value, $alternatives ) ) {
				$alternatives[] = $value;
			}
		}
		$sortMethod = $this->getSortMethodByValues( $alternatives );
		$sortMethod( $alternatives );
		return $alternatives;
	}

	public function getSortMethodByValues( $array ) {
		return str_replace('k','', $this->getSortMethod( $array, true ));
	}
	
	public function getSortMethod( $array, $byValues=false ) {
		$is_numeric = true;
		
		if( !$byValues ) {
			$array = array_keys( $array );
		}
		foreach( $array as $key ) {
			if( empty( $key ) || $key == $this->IKKE_SVAR ) {
				continue;
			}
			if( $is_numeric && is_numeric( $key ) ) {
				continue;
			}
			$is_numeric = false;
		}
		return ($is_numeric ? 'kr' : 'k') . 'sort';
	}
	
	
	
	const PREFIX = 'Excel/';
	
	public static function header( $data, $col ) {
		return $data->getValue( $col.'1' );
	}
	
	public static function count(Environment $environment, $data, $col, $options=false ) {
		$visKunSvar = isset( $options['kunSvar'] ) && $options['kunSvar'] == true;

		$num_rows = $data->getNumRows();
		$rows = $data->countOccurences( $col, $visKunSvar );

		if( !$visKunSvar ) {
			$rowCount = $num_rows;
		} else {
			$rowCount = 0;
			foreach( $rows as $count ) {
				$rowCount += $count;
			}
		}
		
		$data = [
			'id' => $col .'-count',
			'num_rows'	=> $rowCount,
			'alternatives' => $data->getAlternatives( $col, $visKunSvar ),
			'data'	=> $rows,
			'header' => self::header( $data, $col ),
		];
		if( is_array( $options ) ) {
			foreach( $options as $option_name => $option_value ) {
				$data[ $option_name ] = $option_value;
			}
		}

		
		return $environment->render( self::PREFIX . 'count.html.twig', $data );
	}

	public static function countByCol(Environment $environment, $data, $col, $groupCol, $options=false ) {
		$visKunSvar = isset( $options['kunSvar'] ) && $options['kunSvar'] == true;

		$data = [
			'id' => $col .'-by-'. $groupCol,
			'num_rows'	=> $data->getNumRows(),
			'alternatives' => $data->getAlternatives( $col, $visKunSvar ),
			'data'	=> $data->countOccurencesGroupBy( $col, $groupCol, $visKunSvar ),
			'header' => self::header( $data, $col ),
		];
		if( is_array( $options ) ) {
			foreach( $options as $option_name => $option_value ) {
				$data[ $option_name ] = $option_value;
			}
		}
		
		return $environment->render( self::PREFIX . 'group.html.twig', $data );
	}
}