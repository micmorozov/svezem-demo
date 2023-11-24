#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 67 - Экспресс перевозка

Fact -> "экспресс" | "срочно" | "быстро";

S -> Fact interp(Category.Category_67);
