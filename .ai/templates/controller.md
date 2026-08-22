# Controller Template

```
class ControllerName extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('ModelName');
    }

    public function index() {
        $data['items'] = $this->ModelName->getAll();
        $this->load->view('view_name', $data);
    }

    public function create() {
        $this->form_validation->set_rules('field', 'Label', 'required');
        if ($this->form_validation->run() === FALSE) {
            $this->load->view('form_view');
        } else {
            $this->ModelName->insert($this->input->post());
            redirect('/path');
        }
    }

    public function edit($id) {
        $data['item'] = $this->ModelName->getById($id);
        $this->load->view('form_view', $data);
    }

    public function delete($id) {
        $this->ModelName->delete($id);
        redirect('/path');
    }
}
```
