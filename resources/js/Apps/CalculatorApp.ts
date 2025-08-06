// Calculator App - Modular implementation
import { BaseApp, type AppContext } from './BaseApp';
import type { App, UseWindowOptions } from '../Core/Types';

interface CalculatorState {
  currentOperand: string;
  previousOperand: string;
  operation: string | null;
  displayNeedsReset: boolean;
  memoryValue: number;
  history: Array<{ expression: string; result: string; timestamp: Date }>;
}

export class CalculatorApp extends BaseApp {
  private state: CalculatorState = {
    currentOperand: '0',
    previousOperand: '',
    operation: null,
    displayNeedsReset: false,
    memoryValue: 0,
    history: [],
  };

  private displayHistory?: HTMLElement;
  private displayCurrentInput?: HTMLElement;
  private historyContainer?: HTMLElement;

  constructor() {
    const appInfo: App = {
      id: 'calculator',
      name: 'Calculator',
      icon: 'fas fa-calculator',
      iconType: 'fontawesome',
      iconBackground: 'blue-icon',
      component: 'CalculatorApp',
      category: 'utilities',
      permissions: ['read'],
      installed: true,
      system: true,
      teamScoped: false,
      version: '1.0.0',
      description: 'Simple calculator app',
    };

    super('calculator', appInfo);
  }

  protected getWindowOptions(): UseWindowOptions {
    return {
      minWidth: 320,
      minHeight: 480,
      maxWidth: 400,
      maxHeight: 600,
      resizable: true,
      draggable: true,
      centered: true,
    };
  }

  protected async render(): Promise<void> {
    if (!this.context) return;

    this.context.contentElement.innerHTML = `
      <div class="calculator-app">
        <div class="calc-display">
          <div class="calc-history" id="calc-history"></div>
          <div class="calc-current-input" id="calc-current-input">0</div>
        </div>
        <div class="calc-buttons">
          <div class="calc-row">
            <button class="calc-btn function-btn" data-action="clear-all">C</button>
            <button class="calc-btn function-btn" data-action="clear-entry">CE</button>
            <button class="calc-btn function-btn" data-action="backspace">⌫</button>
            <button class="calc-btn operator-btn" data-operation="divide">÷</button>
          </div>
          <div class="calc-row">
            <button class="calc-btn number-btn" data-number="7">7</button>
            <button class="calc-btn number-btn" data-number="8">8</button>
            <button class="calc-btn number-btn" data-number="9">9</button>
            <button class="calc-btn operator-btn" data-operation="multiply">×</button>
          </div>
          <div class="calc-row">
            <button class="calc-btn number-btn" data-number="4">4</button>
            <button class="calc-btn number-btn" data-number="5">5</button>
            <button class="calc-btn number-btn" data-number="6">6</button>
            <button class="calc-btn operator-btn" data-operation="subtract">−</button>
          </div>
          <div class="calc-row">
            <button class="calc-btn number-btn" data-number="1">1</button>
            <button class="calc-btn number-btn" data-number="2">2</button>
            <button class="calc-btn number-btn" data-number="3">3</button>
            <button class="calc-btn operator-btn add-btn" data-operation="add" rowspan="2">+</button>
          </div>
          <div class="calc-row">
            <button class="calc-btn number-btn zero-btn" data-number="0" colspan="2">0</button>
            <button class="calc-btn number-btn" data-number=".">.</button>
            <button class="calc-btn equals-btn" data-action="equals">=</button>
          </div>
          <div class="calc-row memory-row">
            <button class="calc-btn memory-btn" data-action="memory-clear">MC</button>
            <button class="calc-btn memory-btn" data-action="memory-recall">MR</button>
            <button class="calc-btn memory-btn" data-action="memory-add">M+</button>
            <button class="calc-btn memory-btn" data-action="memory-subtract">M−</button>
          </div>
        </div>
        <div class="calc-history-panel" id="calc-history-panel">
          <div class="calc-history-header">
            <h4>History</h4>
            <button class="calc-btn function-btn" data-action="clear-history">Clear</button>
          </div>
          <div class="calc-history-list" id="calc-history-list"></div>
        </div>
      </div>
    `;

    // Setup DOM references
    this.displayHistory = this.context.contentElement.querySelector('#calc-history') || undefined;
    this.displayCurrentInput = this.context.contentElement.querySelector('#calc-current-input') || undefined;
    this.historyContainer = this.context.contentElement.querySelector('#calc-history-list') || undefined;

    // Setup event listeners
    this.setupCalculatorEventListeners();

    // Update initial display
    this.updateDisplay();

    // Load saved state if available
    await this.loadState();
  }

  private setupCalculatorEventListeners(): void {
    if (!this.context) return;

    const buttons = this.context.contentElement.querySelectorAll('.calc-btn');
    
    buttons.forEach(button => {
      this.addEventListener(button as HTMLElement, 'click', (e) => {
        const target = e.target as HTMLElement;
        this.handleButtonClick(target);
      });
    });

    // Keyboard support
    const keydownHandler = (e: KeyboardEvent) => {
      if (!this.isActive()) return;
      this.handleKeyPress(e);
    };
    
    document.addEventListener('keydown', keydownHandler);
    this.eventListeners.push(() => document.removeEventListener('keydown', keydownHandler));
  }

  private handleButtonClick(button: HTMLElement): void {
    const number = button.dataset.number;
    const operation = button.dataset.operation;
    const action = button.dataset.action;

    if (number !== undefined) {
      this.appendNumber(number);
    } else if (operation) {
      this.chooseOperation(operation);
    } else if (action) {
      this.handleAction(action);
    }

    this.updateDisplay();
    this.saveState();
  }

  private handleKeyPress(e: KeyboardEvent): void {
    if (e.ctrlKey || e.altKey || e.metaKey) return;

    e.preventDefault();

    const key = e.key;

    if (/[0-9.]/.test(key)) {
      this.appendNumber(key);
    } else if (key === '+') {
      this.chooseOperation('add');
    } else if (key === '-') {
      this.chooseOperation('subtract');
    } else if (key === '*') {
      this.chooseOperation('multiply');
    } else if (key === '/') {
      this.chooseOperation('divide');
    } else if (key === 'Enter' || key === '=') {
      this.handleAction('equals');
    } else if (key === 'Escape') {
      this.handleAction('clear-all');
    } else if (key === 'Backspace') {
      this.handleAction('backspace');
    } else if (key === 'Delete') {
      this.handleAction('clear-entry');
    }

    this.updateDisplay();
    this.saveState();
  }

  private appendNumber(number: string): void {
    if (number === '.' && this.state.currentOperand.includes('.')) return;
    
    if (this.state.currentOperand === '0' || this.state.displayNeedsReset) {
      this.state.currentOperand = number === '.' ? '0.' : number;
      this.state.displayNeedsReset = false;
    } else {
      if (this.state.currentOperand.length >= 16) return; // Limit input length
      this.state.currentOperand += number;
    }
  }

  private chooseOperation(selectedOperation: string): void {
    if (this.state.currentOperand === '' && this.state.previousOperand === '') return;
    
    if (this.state.currentOperand === '' && this.state.previousOperand !== '') {
      // Allow changing operator
      this.state.operation = selectedOperation;
      return;
    }
    
    if (this.state.previousOperand !== '') {
      this.compute();
    }
    
    this.state.operation = selectedOperation;
    this.state.previousOperand = this.state.currentOperand;
    this.state.currentOperand = '';
    this.state.displayNeedsReset = false;
  }

  private compute(): void {
    const prev = parseFloat(this.state.previousOperand);
    const current = parseFloat(this.state.currentOperand);
    
    if (isNaN(prev) || isNaN(current)) return;

    const expression = `${this.state.previousOperand} ${this.getDisplayOperation(this.state.operation!)} ${this.state.currentOperand}`;
    let computation: number;

    switch (this.state.operation) {
      case 'add':
        computation = prev + current;
        break;
      case 'subtract':
        computation = prev - current;
        break;
      case 'multiply':
        computation = prev * current;
        break;
      case 'divide':
        if (current === 0) {
          this.state.currentOperand = "Error";
          this.state.operation = null;
          this.state.previousOperand = '';
          this.state.displayNeedsReset = true;
          return;
        }
        computation = prev / current;
        break;
      default:
        return;
    }

    let result = String(computation);
    
    // Format result
    if (result.includes('.')) {
      const parts = result.split('.');
      if (parts[1] && parts[1].length > 8) {
        result = parseFloat(result).toFixed(8);
      }
    }
    
    if (result.length > 16) {
      result = parseFloat(result).toExponential(8);
    }

    // Add to history
    this.addToHistory(expression, result);

    this.state.currentOperand = result;
    this.state.operation = null;
    this.state.previousOperand = '';
    this.state.displayNeedsReset = true;
  }

  private handleAction(action: string): void {
    switch (action) {
      case 'equals':
        if (this.state.operation && this.state.previousOperand && this.state.currentOperand) {
          this.compute();
        }
        break;
      case 'clear-all':
        this.clearAll();
        break;
      case 'clear-entry':
        this.clearEntry();
        break;
      case 'backspace':
        this.backspace();
        break;
      case 'memory-clear':
        this.state.memoryValue = 0;
        break;
      case 'memory-recall':
        this.state.currentOperand = String(this.state.memoryValue);
        this.state.displayNeedsReset = true;
        break;
      case 'memory-add':
        this.state.memoryValue += parseFloat(this.state.currentOperand) || 0;
        break;
      case 'memory-subtract':
        this.state.memoryValue -= parseFloat(this.state.currentOperand) || 0;
        break;
      case 'clear-history':
        this.clearHistory();
        break;
    }
  }

  private clearAll(): void {
    this.state.currentOperand = '0';
    this.state.previousOperand = '';
    this.state.operation = null;
    this.state.displayNeedsReset = false;
  }

  private clearEntry(): void {
    this.state.currentOperand = '0';
    this.state.displayNeedsReset = false;
  }

  private backspace(): void {
    if (this.state.displayNeedsReset) {
      this.clearAll();
      return;
    }

    if (this.state.currentOperand.length > 1) {
      this.state.currentOperand = this.state.currentOperand.slice(0, -1);
    } else {
      this.state.currentOperand = '0';
    }
  }

  private addToHistory(expression: string, result: string): void {
    this.state.history.unshift({
      expression,
      result,
      timestamp: new Date(),
    });

    // Keep only last 50 calculations
    if (this.state.history.length > 50) {
      this.state.history = this.state.history.slice(0, 50);
    }

    this.updateHistoryDisplay();
  }

  private clearHistory(): void {
    this.state.history = [];
    this.updateHistoryDisplay();
  }

  private updateDisplay(): void {
    if (!this.displayCurrentInput || !this.displayHistory) return;

    this.displayCurrentInput.textContent = this.state.currentOperand;
    
    if (this.state.operation != null) {
      this.displayHistory.textContent = `${this.state.previousOperand} ${this.getDisplayOperation(this.state.operation)}`;
    } else {
      this.displayHistory.textContent = '';
    }
  }

  private updateHistoryDisplay(): void {
    if (!this.historyContainer) return;

    this.historyContainer.innerHTML = '';

    this.state.history.forEach(item => {
      const historyItem = this.createElement('div', 'calc-history-item');
      historyItem.innerHTML = `
        <div class="calc-history-expression">${item.expression}</div>
        <div class="calc-history-result">${item.result}</div>
      `;

      // Click to use result
      this.addEventListener(historyItem, 'click', () => {
        this.state.currentOperand = item.result;
        this.state.displayNeedsReset = true;
        this.updateDisplay();
      });

      this.historyContainer?.appendChild(historyItem);
    });
  }

  private getDisplayOperation(op: string): string {
    switch (op) {
      case 'add': return '+';
      case 'subtract': return '−';
      case 'multiply': return '×';
      case 'divide': return '÷';
      default: return '';
    }
  }

  private async loadState(): Promise<void> {
    try {
      const savedState = this.getData('calculatorState');
      if (savedState) {
        this.state = { ...this.state, ...savedState };
        this.updateDisplay();
        this.updateHistoryDisplay();
      }
    } catch (error) {
      console.warn('Failed to load calculator state:', error);
    }
  }

  private async saveState(): Promise<void> {
    try {
      this.setData('calculatorState', this.state);
    } catch (error) {
      console.warn('Failed to save calculator state:', error);
    }
  }

  // Lifecycle hooks
  async onMount(context: AppContext): Promise<void> {
    console.log('Calculator app mounted');
  }

  async onUnmount(context: AppContext): Promise<void> {
    await this.saveState();
    console.log('Calculator app unmounted');
  }

  onResize(context: AppContext, size: { width: number; height: number }): void {
    // Calculator doesn't need special resize handling
  }
}

export default CalculatorApp; 