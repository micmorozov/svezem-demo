#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 74 - Перевозка с манипулятором

Fact -> "манипулятор" | "варовайка";

S -> Fact interp(Category.Category_74);
