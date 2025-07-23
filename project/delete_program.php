<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkRole(['exco_user', 'exco_pa', 'admin']);

$database = new Database();
$db = $database->getConnection();

$program_id = isset($_GET['id']) ? $_GET['id'] : 0;

if ($program_id) {
    // Get program details first
    $program_query = "SELECT * FROM programs WHERE id = ?";
    $stmt = $db->prepare($program_query);
    $stmt->execute([$program_id]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($program) {
        // Check permissions
        $can_delete = false;
        if ($_SESSION['role'] == 'admin') {
            $can_delete = true;
        } elseif (in_array($_SESSION['role'], ['exco_user', 'exco_pa']) && $program['created_by'] == $_SESSION['user_id'] && $program['status'] == 'Draft') {
            $can_delete = true;
        }
        
        if ($can_delete) {
            try {
                $db->beginTransaction();
                
                // Get all documents for this program
                $docs_query = "SELECT document_path FROM documents WHERE program_id = ?";
                $docs_stmt = $db->prepare($docs_query);
                $docs_stmt->execute([$program_id]);
                $documents = $docs_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Delete physical files
                foreach ($documents as $doc) {
                    if (file_exists($doc['document_path'])) {
                        unlink($doc['document_path']);
                    }
                }
                
                // Delete program (cascade will handle related records)
                $delete_query = "DELETE FROM programs WHERE id = ?";
                $delete_stmt = $db->prepare($delete_query);
                $delete_stmt->execute([$program_id]);
                
                $db->commit();
                
                header('Location: program_management.php?deleted=1');
                exit();
                
            } catch (Exception $e) {
                $db->rollback();
                header('Location: program_management.php?error=delete_failed');
                exit();
            }
        } else {
            header('Location: unauthorized.php');
            exit();
        }
    }
}

header('Location: program_management.php');
exit();
?>