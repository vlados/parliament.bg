#!/usr/bin/env python3
"""
LangExtract implementation for extracting structured information from Bulgarian Parliament transcripts
"""

import sys
import json
import os
import warnings
import langextract as lx
from typing import List, Dict, Any

# Suppress warnings and progress output
warnings.filterwarnings("ignore")
os.environ['LANGEXTRACT_VERBOSE'] = '0'

# Configure Gemini API
GEMINI_API_KEY = os.environ.get('GEMINI_API_KEY', '')

class ParliamentProtocolExtractor:
    """Extract structured information from parliament transcripts"""
    
    def __init__(self, api_key: str = None):
        self.api_key = api_key or GEMINI_API_KEY
        if not self.api_key:
            raise ValueError("GEMINI_API_KEY is required")
    
    def extract_bill_discussions(self, text: str) -> Dict[str, Any]:
        """Extract bill discussions from transcript"""
        
        prompt = """
        Extract all bill discussions from this parliament transcript.
        For each bill discussed, extract:
        - Bill identifier/number
        - Bill title
        - Type of discussion (first reading, second reading, final vote, etc.)
        - Key speakers and their positions
        - Amendments proposed
        - Voting results if available
        - Decision/outcome
        """
        
        examples = [
            lx.data.ExampleData(
                text="Председателят обяви разглеждането на законопроект № 402-01-45 за изменение на Закона за енергетиката. След дебати, в които участваха народните представители Иванов и Петров, законопроектът беше приет на първо четене с 142 гласа 'за', 45 'против' и 12 'въздържали се'.",
                extractions=[
                    lx.data.Extraction(
                        extraction_class="bill_number",
                        extraction_text="402-01-45"
                    ),
                    lx.data.Extraction(
                        extraction_class="bill_title", 
                        extraction_text="Законопроект за изменение на Закона за енергетиката"
                    ),
                    lx.data.Extraction(
                        extraction_class="reading",
                        extraction_text="първо четене"
                    ),
                    lx.data.Extraction(
                        extraction_class="outcome",
                        extraction_text="приет"
                    )
                ]
            )
        ]
        
        try:
            # Redirect stdout to suppress progress indicators
            import io
            from contextlib import redirect_stdout, redirect_stderr
            
            with redirect_stdout(io.StringIO()), redirect_stderr(io.StringIO()):
                result = lx.extract(
                    text_or_documents=text,
                    prompt_description=prompt,
                    examples=examples,
                    model_id="gemini-1.5-flash",
                    api_key=self.api_key
                )
            
            return self._format_extraction_result(result)
        except Exception as e:
            import traceback
            return {
                "error": str(e),
                "traceback": traceback.format_exc()
            }
    
    def extract_committee_decisions(self, text: str) -> Dict[str, Any]:
        """Extract committee decisions from transcript"""
        
        prompt = """
        Extract all committee decisions from this parliament transcript.
        For each decision, extract:
        - Decision type
        - Subject matter
        - Committee members present
        - Voting results
        - Final decision/resolution
        - Any follow-up actions required
        """
        
        try:
            # Redirect stdout to suppress progress indicators
            import io
            from contextlib import redirect_stdout, redirect_stderr
            
            with redirect_stdout(io.StringIO()), redirect_stderr(io.StringIO()):
                result = lx.extract(
                    text_or_documents=text,
                    prompt_description=prompt,
                    model_id="gemini-1.5-flash",
                    api_key=self.api_key
                )
            
            return self._format_extraction_result(result)
        except Exception as e:
            import traceback
            return {
                "error": str(e),
                "traceback": traceback.format_exc()
            }
    
    def extract_amendments(self, text: str) -> Dict[str, Any]:
        """Extract proposed amendments from transcript"""
        
        prompt = """
        Extract all proposed amendments from this parliament transcript.
        For each amendment, extract:
        - Amendment identifier
        - Related bill or law
        - Proposer (person or party)
        - Amendment description
        - Support/opposition
        - Voting results if available
        - Status (accepted/rejected/pending)
        """
        
        try:
            # Redirect stdout to suppress progress indicators
            import io
            from contextlib import redirect_stdout, redirect_stderr
            
            with redirect_stdout(io.StringIO()), redirect_stderr(io.StringIO()):
                result = lx.extract(
                    text_or_documents=text,
                    prompt_description=prompt,
                    model_id="gemini-1.5-flash",
                    api_key=self.api_key
                )
            
            return self._format_extraction_result(result)
        except Exception as e:
            import traceback
            return {
                "error": str(e),
                "traceback": traceback.format_exc()
            }
    
    def extract_speaker_statements(self, text: str) -> Dict[str, Any]:
        """Extract and summarize speaker statements"""
        
        prompt = """
        Extract all speaker statements from this parliament transcript.
        For each speaker, extract:
        - Speaker name
        - Political affiliation/party
        - Key points made
        - Position on discussed matters
        - Any motions or proposals made
        """
        
        try:
            # Redirect stdout to suppress progress indicators
            import io
            from contextlib import redirect_stdout, redirect_stderr
            
            with redirect_stdout(io.StringIO()), redirect_stderr(io.StringIO()):
                result = lx.extract(
                    text_or_documents=text,
                    prompt_description=prompt,
                    model_id="gemini-1.5-flash",
                    api_key=self.api_key
                )
            
            return self._format_extraction_result(result)
        except Exception as e:
            import traceback
            return {
                "error": str(e),
                "traceback": traceback.format_exc()
            }
    
    def extract_protocol_changes(self, text: str, extraction_type: str = 'all') -> Dict[str, Any]:
        """Main extraction method that routes to specific extractors"""
        
        extractors = {
            'bill_discussions': self.extract_bill_discussions,
            'committee_decisions': self.extract_committee_decisions,
            'amendments': self.extract_amendments,
            'speaker_statements': self.extract_speaker_statements
        }
        
        if extraction_type == 'all':
            results = {}
            for name, extractor in extractors.items():
                results[name] = extractor(text)
            return results
        elif extraction_type in extractors:
            return extractors[extraction_type](text)
        else:
            return {"error": f"Unknown extraction type: {extraction_type}"}
    
    def _format_extraction_result(self, result) -> Dict[str, Any]:
        """Format LangExtract result for JSON output"""
        try:
            # Handle LangExtract result object
            if hasattr(result, 'extractions') and hasattr(result.extractions, '__iter__'):
                formatted_data = []
                for extraction in result.extractions:
                    # Extract the key information from each extraction
                    extraction_dict = {
                        'extraction_class': getattr(extraction, 'extraction_class', 'unknown'),
                        'extraction_text': getattr(extraction, 'extraction_text', ''),
                        'attributes': getattr(extraction, 'attributes', {}),
                        'description': getattr(extraction, 'description', None)
                    }
                    # Clean up None values
                    extraction_dict = {k: v for k, v in extraction_dict.items() if v is not None}
                    formatted_data.append(extraction_dict)
                    
                return {"extractions": formatted_data}
            elif hasattr(result, '__iter__') and not isinstance(result, str):
                # Handle iterable results
                formatted_data = []
                for item in result:
                    if hasattr(item, 'extraction_class'):
                        formatted_data.append({
                            'extraction_class': getattr(item, 'extraction_class', 'unknown'),
                            'extraction_text': getattr(item, 'extraction_text', ''),
                            'attributes': getattr(item, 'attributes', {})
                        })
                    else:
                        formatted_data.append(str(item))
                return {"extractions": formatted_data}
            else:
                return {"raw_result": str(result)}
        except Exception as e:
            import traceback
            return {
                "error": f"Failed to format result: {str(e)}", 
                "raw_result": str(result),
                "traceback": traceback.format_exc()
            }


def main():
    """Main entry point for command-line usage"""
    
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Usage: python extract_protocol_changes.py <transcript_file> [options]"}))
        sys.exit(1)
    
    transcript_file = sys.argv[1]
    options = json.loads(sys.argv[2]) if len(sys.argv) > 2 else {}
    
    # Read transcript content
    try:
        with open(transcript_file, 'r', encoding='utf-8') as f:
            content = f.read()
    except Exception as e:
        print(json.dumps({"error": f"Failed to read file: {str(e)}"}))
        sys.exit(1)
    
    # Get API key from environment or options
    api_key = options.get('api_key') or os.environ.get('GEMINI_API_KEY')
    
    if not api_key:
        # Try to read from Laravel .env file
        env_path = os.path.join(os.path.dirname(__file__), '..', '.env')
        if os.path.exists(env_path):
            with open(env_path, 'r') as f:
                for line in f:
                    if line.startswith('GEMINI_API_KEY='):
                        api_key = line.split('=', 1)[1].strip().strip('"\'')
                        break
    
    if not api_key:
        print(json.dumps({"error": "GEMINI_API_KEY not found in environment or .env file"}))
        sys.exit(1)
    
    # Initialize extractor
    try:
        extractor = ParliamentProtocolExtractor(api_key)
    except Exception as e:
        print(json.dumps({"error": f"Failed to initialize extractor: {str(e)}"}))
        sys.exit(1)
    
    # Perform extraction
    extraction_type = options.get('extraction_type', 'all')
    
    try:
        results = extractor.extract_protocol_changes(content, extraction_type)
        print(json.dumps(results, ensure_ascii=False, indent=2))
    except Exception as e:
        print(json.dumps({"error": f"Extraction failed: {str(e)}"}))
        sys.exit(1)


if __name__ == "__main__":
    main()