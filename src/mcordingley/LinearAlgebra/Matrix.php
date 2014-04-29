<?php

namespace mcordingley\LinearAlgebra;

class Matrix implements \ArrayAccess {
    protected $rowCount;
    protected $columnCount;

    // Internal array representation of the matrix
    protected $internal;
    
    /**
     * Constructor
     * 
     * Creates a new matrix. e.g. 
     *      $transform = new Matrix([
     *          [0, 1, 2],
     *          [3, 4, 5],
     *          [6, 7, 8]
     *      ]);
     * 
     * @param array $literal Array representation of the matrix.
     */
    public function __construct(array $literal) {
        if (!$this->isLiteralValid($literal)) {
            throw new MatrixException('Invalid array provided: ' . print_r($literal, true));
        }
        
        $this->internal = $literal;
        
        $this->rowCount = count($literal);
        $this->columnCount = count($literal[0]);
    }
    
    // Tests an array representation of a matrix to see if it would make a valid matrix
    protected function isLiteralValid(array $literal) {
        // Matrix must have at least one row
        if (!count($literal)) {
            return false;
        }
        
        // Matrix must have at least one column
        if (!count($literal[0])) {
            return false;
        }
        
        // Matrix must have the same number of columns in each row
        $lastRow = false;
        foreach ($literal as $row) {
            $thisRow = count($row);
            
            if ($lastRow !== false && $lastRow != $thisRow) {
                return false;
            }
            
            $lastRow = $thisRow;
        }
        
        return true;
    }
    
    // Potentially a good thing to take public. We'll see if that's a good idea.
    protected function isSquare() {
        return $this->rows == $this->columns;
    }
    
    protected function isSymmetric() {
        if (!$this->isSquare()) {
            return false;
        }
        
        for ($i = 0; $i < $this->rows; ++$i) {
            for ($j = 0; $j < $this->columns; ++$j) {
                if ($i == $j) {
                    continue;
                }
                
                if ($this->get($i, $j) != $this->get($j, $i)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * identity
     * 
     * @param int $size How many rows and columns the identity matrix should have
     * @return \mcordingley\LinearAlgebra\Matrix A new identity matrix of size $size
     * @static
     */
    public static function identity($size) {
        $literal = array();
        
        for ($i = 0; $i < $size; ++$i) {
            $literal[] = array();
            
            for ($j = 0; $j < $size; ++$j) {
                $literal[$i][] = ($i == $j) ? 1 : 0;
            }
        }
        
        return new static($literal);
    }


    /**
     * map
     * 
     * Iterates over the current matrix with a callback function to return a new
     * matrix with the mapped values. $callback takes four arguments:
     * - The current matrix element
     * - The current row
     * - The current column
     * - The matrix being iterated over
     * 
     * @param callable $callback A function that returns the computed new values.
     * @return \mcordingley\LinearAlgebra\Matrix A new matrix with the mapped values.
     */
    public function map(callable $callback) {
        $literal = array();

        for ($i = 0; $i < $this->rows; $i++) {
            $row = array();

            for ($j = 0; $j < $this->columns; $j++) {
                $row[] = $callback($this->get($i, $j), $i, $j, $this);
            }

            $literal[] = $row;
        }
        
        return new static($literal);
    }
    
    /**
     * get
     * 
     * @param int $row Which zero-based row index to access.
     * @param int $column Which zero-based column index to access.
     * @return numeric The value at $row, $column position in the matrix.
     */
    public function get($row, $column) {
        return $this->internal[$row][$column];
    }
    
    /**
     * set
     * 
     * Alters the current matrix to have a new value and then returns $this for
     * method chaining.
     * 
     * @param int $row Which zero-based row index to set.
     * @param int $column Which zero-based column index to set.
     * @param numeric $value The new value for the position at $row, $column.
     * @return \mcordingley\LinearAlgebra\Matrix
     */
    public function set($row, $column, $value) {
        $this->internal[$row][$column] = $value;
        
        return $this;
    }
    
    /**
     * eq
     * 
     * Checks to see if two matrices are equal in value.
     * 
     * @param \mcordingley\LinearAlgebra\Matrix $matrixB
     * @return boolean True if equal. False otherwise.
     */
    public function eq(\mcordingley\LinearAlgebra\Matrix $matrixB) {
        if ($this->rowCount != $matrixB->rowCount || $this->columnCount != $matrixB->columnCount) {
            return false;
        }
        
        for ($i = $this->rowCount; $i--; ) {
            for ($j = $this->columnCount; $j--; ) {
                if ($this->get($i, $j) != $matrixB->get($i, $j)) {
                    return false;
                }
            }
        }
        
        return true;
    }


    /**
     * add
     * 
     * Adds either another matrix or a scalar to the current matrix, returning
     * a new matrix instance.
     * 
     * @param mixed $value Matrix or scalar to add to this matrix
     * @return \mcordingley\LinearAlgebra\Matrix New matrix with the added value
     * @throws MatrixException
     */
    public function add($value) {
        if ($value instanceof Matrix) {
            if ($this->rows != $value->rows || $this->columns != $value->columns) {
                throw new MatrixException('Cannot add two matrices of different size.');
            }
            
            return $this->map(function($element, $i, $j) use ($value) {
                return $element + $value->get($i, $j);
            });
        }
        else {
            return $this->map(function($element) use ($value) {
                return $element + $value;
            });
        }
    }
    
    /**
     * subtract
     * 
     * Subtracts either another matrix or a scalar from the current matrix,
     * returning a new matrix instance.
     * 
     * @param mixed $value Matrix or scalar to subtract from this matrix
     * @return \mcordingley\LinearAlgebra\Matrix New matrix with the subtracted value
     * @throws MatrixException
     */
    public function subtract($value) {
        if ($value instanceof Matrix) {
            if ($this->rows != $value->rows || $this->columns != $value->columns) {
                throw new MatrixException('Cannot subtract two matrices of different size.');
            }
            
            return $this->map(function($element, $i, $j) use ($value) {
                return $element - $value->get($i, $j);
            });
        }
        else {
            return $this->map(function($element) use ($value) {
                return $element - $value;
            });
        }
    }
    
    /**
     * multiply
     * 
     * Multiplies either another matrix or a scalar with the current matrix,
     * returning a new matrix instance.
     * 
     * @param mixed $value Matrix or scalar to multiply with tnis matrix
     * @return \mcordingley\LinearAlgebra\Matrix New multiplied matrix
     * @throws MatrixException
     */
    public function multiply($value) {
        if ($value instanceof Matrix) {
            // TODO: This is another good candidate for optimization. Too many loops!
            
            if ($this->columns != $value->rows) {
                throw new MatrixException('Cannot multiply matrices of these sizes.');
            }
            
            $literal = array();
            
            for ($i = 0; $i < $this->rows; $i++) {
                $row = array();
                
                for ($j = 0; $j < $value->columns; $j++) {
                    $sum = 0;
                    
                    for ($k = 0; $k < $this->columns; $k++) {
                        $sum += $this->get($i, $k) * $value->get($k, $j);
                    }
                    
                    $row[] = $sum;
                }
                
                $literal[] = $row;
            }

            return new static($literal);
        }
        else {
            return $this->map(function($element) use ($value) {
                return $element * $value;
            });
        }
    }
 
    /**
     * trace
     * 
     * Sums the main diagonal values of a square matrix.
     * 
     * @return numeric
     */
    public function trace() {
        if (!$this->isSquare($this)) {
            throw new MatrixException('Trace can only be called on square matrices: ' . print_r($this->literal, true));
        }

        $trace = 0;
        
        for ($i = 0; $i < $this->rows; $i++) {
            $trace += $this->get($i, $i);
        }

        return $trace;
    }
    
    /**
     * transpose
     * 
     * Creates and returns a new matrix that is a transposition of this matrix.
     * 
     * @return \mcordingley\LinearAlgebra\Matrix Transposed matrix.
     */
    public function transpose() {
        $literal = array();
        
        for ($i = 0; $i < $this->columns; $i++) {
            $literal[] = array();
            
            for ($j = 0; $j < $this->rows; $j++) {
                $literal[$i][] = $this->get($j, $i);
            }
        }
        
        return new self($literal);
    }
    
    /**
     * inverse
     * 
     * Creates and returns a new matrix that is the inverse of this matrix.
     * 
     * @return \mcordingley\LinearAlgebra\Matrix The adjoint matrix
     * @throws MatrixException
     */
    public function inverse() {
        if (!$this->isSquare($this)) {
            throw new MatrixException('Inverse can only be called on square matrices: ' . print_r($this->literal, true));
        }
        
        if ($this->determinant() == 0) {
            throw new MatrixException('This matrix has a zero determinant and is therefore not invertable: ' . print_r($this->literal, true));
        }
        
        if ($this->isSymmetric()) {
            try {
                return $this->choleskyInverse();
            }
            catch (\Exception $exception) {
                // Allow this to fall through to the more general algorithm.
            }
        }
        
        // Fall back to a slower, but more general way of calculating the inverse.
        // TODO: Implement a faster algorithm.
        return $this->adjoint()->multiply(1 / $this->determinant());
    }
    
    // Translated from: http://adorio-research.org/wordpress/?p=4560
    private function choleskyInverse() {
        // Cholesky Decomposition
        
        $ztol= 1.0e-5;
        
        $t = array();
        for ($i = 0; $i < $this->rows; ++$i) {
            $t[] = array();
            
            for ($j = 0; $j < $this->rows; ++$j) {
                $t[$i][] = 0;
            }
        }
        
        for ($i = 0; $i < $this->rows; ++$i) {
            $S = 0;
            
            for ($k = 0; $k < $i; ++$i) {
                $S += pow($t[$k][$i], 2);
            }
                
            $d = $this->get($i, $i) - $S;
            
            if (abs($d) < $ztol) {
               $t[i][i] = 0;
            }
            else {
               if ($d < 0) {
                  throw new \Exception("Matrix not positive-definite");
               }
               
               $t[i][i] = sqrt(d);
            }
            
            for ($j = $i + 1; $j < $this->rows; ++$j) {
                $S = 0;
            
                for ($k = 0; $k < $i; ++$i) {
                    $S += $t[$k][$i] * $t[$k][$j];
                }
                   
                if (abs($S) < $ztol) {
                    $S = 0;
                }
               
                try {
                    $t[$i][$j] = ($this->internal[$i][$j] - $S) / $t[$i][$i];
                }
                catch (\Exception $exception) {
                    throw new Exception("Zero diagonal");
                }
            }
        }
        
        // Cholesky Inverse

        $B = array();
        
        for ($i = 0; $i < $this->rowCount; ++$i) {
            $B[] = array();
            
            for ($j = 0; $j < $this->rowCount; ++$j) {
                $B[$i][] = 0;
            }
        }

        for ($j = $this->rowCount; $j--; ) {
            $tjj = $t[$j][$j];
            
            $S = 0;
            for ($k = $j + 1; $k < $this->rowCount; ++$j) {
                $S += $t[$j][$k] * $B[$j][$k];
            }
            
            $B[$j][$j] = 1 / pow($tjj, 2) - $S / $tjj;
            
            for ($i = $j; $j--; ) {
                $sum = 0;
                
                for ($k = $i + 1; $i < $this->rowCount; ++$i) {
                    $sum += $t[$i][$k] * $B[$k][$j];
                }
                        
                $B[$j][$i] = $B[$i][$j] = -$sum / $t[$i][$i];
            }
        }
        
        return new static($B);
    }
 
    /**
     * adjoint
     * 
     * Creates and returns a new matrix that is the adjoint of this matrix.
     * 
     * @return \mcordingley\LinearAlgebra\Matrix The adjoint matrix
     * @throws MatrixException
     */
    public function adjoint() {
        if (!$this->isSquare($this)) {
            throw new MatrixException('Adjoints can only be called on square matrices: ' . print_r($this->literal, true));
        }
        
        return $this->map(function($element, $i, $j, $matrix) {
            return pow(-1, $i + $j) * $matrix->submatrix($i, $j)->determinant();
        })->transpose();
    }
    
    /**
      * Determinant function
      *
      * Returns the determinant of the matrix
      *
      * @return float The matrix's determinant
      */
    public function determinant() {
        /* TODO: This function is a good candidate for optimization by the
                 mathematically-inclined. Suggest doing the operation without
                 generating new matrices during the calculation. */
        
        if (!$this->isSquare($this)) {
            throw new MatrixException('Determinants can only be called on square matrices: ' . print_r($this->literal, true));
        }

        // Base case for a 1 by 1 matrix
        if ($this->rows == 1) {
            return $this->get(0, 0);
        }

        $sum = 0;
        
        // Statically choose the first row for cofactor expansion, because it
        // doesn't matter which row we choose for it.
        for ($j = 0; $j < $this->columns; $j++) {
            $sum += pow(-1, $j) * $this->get(0, $j) * $this->submatrix(0, $j)->determinant();
        }
        
        return $sum;
    }
    
    /**
     * submatrix
     *
     * Returns a new matrix with the selected row and column removed, useful for
     * calculating determinants or other recursive operations on matrices.
     *
     * @param int $row Row to remove, null to remove no row.
     * @param int $column Column to remove, null to remove no column.
     * @return \mcordingley\LinearAlgebra\Matrix Reduced matrix.
     */
    public function submatrix($row = null, $column = null) {
        $literal = array();

        for ($i = 0; $i < $this->rows; $i++) {
            if ($i === $row) {
                continue;
            }

            $rowLiteral = array();

            for ($j = 0; $j < $this->columns; $j++) {
                if ($j === $column) {
                    continue;
                }

                $rowLiteral[] = $this->get($i, $j);
            }

            $literal[] = $rowLiteral;
        }

        return new static($literal);
    }
    
    public function __get($property) {
        switch ($property) {
            case 'columns':
                return $this->columnCount;
            case 'rows':
                return $this->rowCount;
            default:
                return null;
        }
    }
    
    //
    // Array Access Interface
    //
    
    public function offsetExists($offset) {
        return isset($this->internal[$offset]);
    }
    
    public function offsetGet($offset) {
        return $this->internal[$offset];
    }
    
    // Matrix objects are immutable
    public function offsetSet($offset, $value) {
        return;
    }
    
    // Matrix objects are immutable
    public function offsetUnset($offset) {
        return;
    }
}