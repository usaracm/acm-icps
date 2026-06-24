<?php

namespace CertificateManager\Enums;

use App\Models\Enums\Concern\UsefulEnums;

enum CertificateTemplateType: int
{
	use UsefulEnums;

	case Custom = 99;
	case Submission = 1;
	case Reviewer = 2;
	case Speaker = 3;
	case Participant = 4;
}

